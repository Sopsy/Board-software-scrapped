<?php
namespace YBoard\Controller;

use YBoard\Abstracts\ExtendedController;
use YBoard\Exceptions\FileUploadException;
use YBoard\Library\Cache;
use YBoard\Library\HttpResponse;
use YBoard\Library\ReCaptcha;
use YBoard\Library\Text;
use YBoard\Model\Files;
use YBoard\Model\Posts;
use YBoard\Model\WordBlacklist;

class Post extends ExtendedController
{
    public function get()
    {
        $this->validateAjaxCsrfToken();

        if (empty($_POST['postId'])) {
            $this->throwJsonError(400);
        }

        $posts = new Posts($this->db);
        $post = $posts->get($_POST['postId']);
        if (!$post) {
            $this->throwJsonError(404, _('Post does not exist'));
        }

        if (!empty($post->threadId)) {
            $thread = $posts->getThreadMeta($post->threadId);
        } else {
            $thread = $post;
        }

        $view = $this->loadTemplateEngine('Blank');

        $view->tooltip = true;
        $view->post = $post;
        $view->thread = $thread;
        $view->board = $this->boards->getById($thread->boardId);

        $view->display('Ajax/Post');
    }

    public function redirect($postId)
    {
        $posts = new Posts($this->db);
        $post = $posts->getMeta($postId);

        if (!$post) {
            $this->notFound(_('Not found'), _('Post does not exist'));
        }

        if (!$post->boardId) {
            $thread = $posts->getMeta($post->threadId);

            if (!$thread) {
                $this->internalError();
            }

            $boardId = $thread->boardId;
        } else {
            $boardId = $post->boardId;
        }

        $board = $this->boards->getById($boardId);
        if (!$board) {
            $this->internalError();
        }

        if (empty($post->threadId)) {
            $thread = $post->id;
            $hash = '';
        } else {
            $thread = $post->threadId;
            $hash = '#post-' . $post->id;
        }

        HttpResponse::redirectExit('/' . $board->url . '/' . $thread . $hash, 302);
    }

    public function submit()
    {
        $this->validateAjaxCsrfToken();

        // Check bans
        if ($this->user->isBanned()) {
            $this->throwJsonError(403, _('You are banned!'));
        }

        $posts = new Posts($this->db);

        // Is this a reply or a new thread?
        $isReply = !empty($_POST['thread']);
        $hasFile = !empty($_FILES['files']['tmp_name']);

        // Prepare message
        $message = isset($_POST['message']) ? trim($_POST['message']) : false;
        $hasMessage = !empty($message) || $message === '0';

        if (!$isReply) { // Creating a new thread
            // Verify board
            if (empty($_POST['board']) || !$this->boards->exists($_POST['board'])) {
                $this->throwJsonError(400, _('Invalid board'));
            }

            // Message is required for new threads
            if (!$hasMessage) {
                $this->throwJsonError(400, _('Please type a message'));
            }

            $board = $this->boards->getByUrl($_POST['board']);
        } else { // Replying to a thread
            $thread = $posts->getThreadMeta($_POST['thread']);

            // Verify thread
            if (!$thread) {
                $this->throwJsonError(400, _('Invalid thread'));
            }

            if ($thread->locked) {
                $this->throwJsonError(400, _('This thread is locked'));
            }
            $board = $this->boards->getById($thread->boardId);

            // Message OR file is required for replies
            if (!$hasMessage && !$hasFile) {
                $this->throwJsonError(400, _('Please type a message or choose a file'));
            }
        }

        if ($this->user->requireCaptcha) {
            if (empty($_POST["g-recaptcha-response"])) {
                $this->throwJsonError(400, _('Please fill the CAPTCHA.'));
            }

            $captchaOk = ReCaptcha::verify($_POST["g-recaptcha-response"], $this->config['reCaptcha']['privateKey']);
            if (!$captchaOk) {
                $this->throwJsonError(403, _('Invalid CAPTCHA response. Please try again.'));
            }
        }

        // TODO: This could be done better...
        // TODO: Add ipv6 support
        require_once(__DIR__ . '/../Vendor/ip2location.php');
        $ip2location = new \IP2Location\Database(__DIR__ . '/../Vendor/IP2LOCATION-LITE-DB1.BIN');
        $countryCode = strtoupper($ip2location->lookup($_SERVER['REMOTE_ADDR'], \IP2Location\Database::COUNTRY)['countryCode']);

        // Message options
        $sage = empty($_POST['sage']) ? false : true;
        $hideName = empty($_POST['hidename']) ? false : true;

        if ($this->user->goldLevel == 0) {
            $noCompress = false;
            $goldHide = false;
        } else {
            $noCompress = empty($_POST['nocompress']) ? false : true;
            $goldHide = empty($_POST['goldhide']) ? false : true;
        }

        // Preprocess message
        $message = isset($_POST['message']) ? trim($_POST['message']) : '';
        if (!empty($message)) {
            $message = Text::removeForbiddenUnicode($message);
            $message = Text::limitEmptyLines($message);
            $message = mb_substr($message, 0, $this->config['posts']['messageMaxLength']);
        }

        // Check blacklist
        $wordBlacklist = new WordBlacklist($this->db);
        $blacklistReason = $wordBlacklist->match($message);
        if ($blacklistReason !== false) {
            $this->throwJsonError(403, sprintf(_('Your message contained a blacklisted word: %s'), $blacklistReason));
        }

        $subject = null;
        if (!$isReply && isset($_POST['subject'])) {
            $subject = trim(mb_substr($_POST['subject'], 0, $this->config['posts']['subjectMaxLength']));
        }

        $username = null;
        if (!$hideName && $this->user->goldLevel != 0) {
            $username = $this->user->username;
        }

        // Check that we have enough free space for files
        if (disk_free_space($this->config['files']['savePath']) <= $this->config['files']['diskMinFree']) {
            $this->throwJsonError(400, _('File uploads are temporarily disabled'));
        }

        // Process file
        if ($hasFile) {
            $files = new Files($this->db);
            $files->setConfig($this->config['files']);

            if ($_FILES['files']['size'] >= $this->config['files']['maxSize']) {
                $this->throwJsonError(400, _('Your files exceed the maximum upload size.'));
            }

            try {
                $file = $files->processUpload($_FILES['files']);
            } catch (FileUploadException $e) {
                $this->throwJsonError(500, $e->getMessage());
            }
        }

        if (!$isReply) {
            if (Cache::exists('SpamLimit-thread-'. $_SERVER['REMOTE_ADDR'])) {
                $this->throwJsonError(403, _('You are sending messages too fast. Please don\'t spam.'));
            }

            $postId = $posts->createThread($this->user->id, $board->id, $subject, $message, $username,
                $_SERVER['REMOTE_ADDR'], $countryCode);

            Cache::add('SpamLimit-thread-'. $_SERVER['REMOTE_ADDR'], 1, 30);
        } else {
            if (Cache::exists('SpamLimit-reply-'. $_SERVER['REMOTE_ADDR'])) {
                $this->throwJsonError(403, _('You are sending messages too fast. Please don\'t spam.'));
            }

            $postId = $posts->addReply($this->user->id, $thread->id, $message, $username, $_SERVER['REMOTE_ADDR'],
                $countryCode);

            Cache::add('SpamLimit-reply-'. $_SERVER['REMOTE_ADDR'], 1, 5);
            $posts->updateThreadStats($thread->id, 'replyCount');

            if (!$sage) {
                $posts->bumpThread($thread->id);
            }
        }

        if ($hasFile) {
            $posts->addFile($postId, $file->id, $file->origName);
        }

        // TODO: Save replies
        preg_match_all('/>>([0-9]+)/i', $message, $postReplies);
        $postReplies = array_unique($postReplies[1]);
        //$posts->setReplies($postId, $postReplies);

        // TODO: Save tags
        // TODO: Add notifications

        //$this->throwJsonError(400, $postId);
    }

    public function delete()
    {
        $this->validateAjaxCsrfToken();

        if (empty($_POST['postId'])) {
            $this->throwJsonError(400);
        }

        $posts = new Posts($this->db);
        $post = $posts->getMeta($_POST['postId']);
        if (!$post) {
            $this->throwJsonError(404, _('Post does not exist'));
        }

        if ($post->userId != $this->user->id && !$this->user->isMod) {
            $this->throwJsonError(403, _('This isn\'t your post!'));
        }

        if (!empty($post->threadId)) {
            $posts->updateThreadStats($post->threadId, 'replyCount', -1);
        }

        $posts->delete($_POST['postId']);
    }
}
