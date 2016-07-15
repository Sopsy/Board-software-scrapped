<?php
namespace YBoard\Controller;

use YBoard\Abstracts\ExtendedController;
use YBoard\Library\Cache;
use YBoard\Library\GeoIP;
use YBoard\Library\HttpResponse;
use YBoard\Library\MessageQueue;
use YBoard\Library\ReCaptcha;
use YBoard\Library\Text;
use YBoard\Model\Files;
use YBoard\Model\Log;
use YBoard\Model\Posts;
use YBoard\Model\UserNotifications;
use YBoard\Model\UserThreadFollow;
use YBoard\Model\WordBlacklist;

class Post extends ExtendedController
{
    public function get()
    {
        $this->validateAjaxCsrfToken();

        if (empty($_POST['post_id'])) {
            $this->throwJsonError(400);
        }

        $posts = new Posts($this->db);
        $post = $posts->get($_POST['post_id']);
        if (!$post) {
            $this->throwJsonError(404, _('Post does not exist'));
        }

        if (empty($post->threadId)) {
            $post->threadId = $post->id;
        }
        $thread = $posts->getThreadMeta($post->threadId);

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
        $hasFile = !empty($_POST['file_id']);

        if (!empty($_POST['file_name'])) {
            $fileName = trim(Text::removeForbiddenUnicode($_POST['file_name']));
        }

        // Try getting a file by given name
        if (!$hasFile && !empty($fileName)) {
            $files = new Files($this->db);
            $file = $files->getByName($fileName);
            if (!$file) {
                $this->throwJsonError(404,
                    sprintf(_('File "%s" was not found, please type a different name or choose a file'),
                        htmlspecialchars($fileName)));
            }
            $hasFile = true;
            $_POST['file_id'] = $file->id;
        } elseif ($hasFile && empty($fileName)) {
            $this->throwJsonError(400, _('Please type a file name'));
        }

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

            // At least 20 characters
            if (mb_strlen($message) < 10) {
                $this->throwJsonError(400, _('Please type a longer message'));
            }

            $board = $this->boards->getByUrl($_POST['board']);
        } else { // Replying to a thread
            $thread = $posts->getThreadMeta($_POST['thread']);

            // Verify thread
            if (!$thread) {
                $this->throwJsonError(400, _('Invalid thread'));
            }

            if ($thread->locked && !$this->user->isMod) {
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

        $countryCode = GeoIP::getCountryCode($_SERVER['REMOTE_ADDR']);

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
            $message = mb_substr($message, 0, $this->config['view']['messageMaxLength']);
        }

        // Check blacklist
        $wordBlacklist = new WordBlacklist($this->db);
        $blacklistReason = $wordBlacklist->match($message);
        if ($blacklistReason !== false) {
            $this->throwJsonError(403, sprintf(_('Your message contained a blacklisted word: %s'), $blacklistReason));
        }

        $subject = null;
        if (!$isReply && isset($_POST['subject'])) {
            $subject = trim(mb_substr($_POST['subject'], 0, $this->config['view']['subjectMaxLength']));
        }

        $username = null;
        if (!$hideName && $this->user->goldLevel != 0) {
            $username = $this->user->username;
        }

        $messageQueue = new MessageQueue();
        $notificationsSkipUsers = [];
        if (!$isReply) {
            // Check flood limit
            if (Cache::exists('SpamLimit-thread-' . $_SERVER['REMOTE_ADDR'])) {
                $this->throwJsonError(403, _('You are sending messages too fast. Please don\'t spam.'));
            }

            $postId = $posts->createThread($this->user->id, $board->id, $subject, $message, $username,
                $_SERVER['REMOTE_ADDR'], $countryCode);

            // Increment stats
            $this->user->statistics->increment('createdThreads');

            // Enable flood limit
            Cache::add('SpamLimit-thread-' . $_SERVER['REMOTE_ADDR'], 1, $this->config['posts']['threadIntervalLimit']);
        } else {
            // Check flood limit
            if (Cache::exists('SpamLimit-reply-' . $_SERVER['REMOTE_ADDR'])) {
                $this->throwJsonError(403, _('You are sending messages too fast. Please don\'t spam.'));
            }

            $postId = $posts->addReply($this->user->id, $thread->id, $message, $username, $_SERVER['REMOTE_ADDR'],
                $countryCode);

            // Update stats
            $this->user->statistics->increment('sentReplies');
            $posts->updateThreadStats($thread->id, 'replyCount');

            // Increment followed threads unread count
            $followed = new UserThreadFollow($this->db);
            $followed->incrementUnreadCount($thread->id, $this->user->id);

            // Enable flood limit
            Cache::add('SpamLimit-reply-' . $_SERVER['REMOTE_ADDR'], 1, $this->config['posts']['replyIntervalLimit']);

            if (!$sage) {
                $posts->bumpThread($thread->id);
            }

            if ($thread->userId != $this->user->id) {
                // Notify OP
                $messageQueue->send([
                    UserNotifications::NOTIFICATION_TYPE_THREAD_REPLY,
                    $thread->id,
                    $notificationsSkipUsers,
                ], MessageQueue::MSG_TYPE_ADD_POST_NOTIFICATION);
                $notificationsSkipUsers[] = $thread->userId;
            } else {
                // Mark thread notifications as read for OP
                $this->user->notifications->markReadByPost($thread->id);
            }
        }

        // Save file
        if ($hasFile) {
            $posts->addFile($postId, $_POST['file_id'], $_POST['file_name']);
        }

        // Increment Total message characters -stats
        $this->user->statistics->increment('messageTotalCharacters', mb_strlen($message));

        // Save replies
        preg_match_all('/>>([0-9]+)/i', $message, $postReplies);
        $postReplies = array_unique($postReplies[1]);
        $posts->setPostReplies($postId, $postReplies);

        // TODO: Save tags

        // Notify all replied users
        if (!empty($postReplies)) {
            $notificationsSkipUsers[] = $this->user->id;
            $messageQueue->send([
                UserNotifications::NOTIFICATION_TYPE_POST_REPLY,
                $postReplies,
                $notificationsSkipUsers
            ], MessageQueue::MSG_TYPE_ADD_POST_NOTIFICATION);
        }

        if (!$isReply) {
            $this->jsonMessage($postId);
        }
    }

    public function delete()
    {
        $this->validateAjaxCsrfToken();

        if (empty($_POST['post_id'])) {
            $this->throwJsonError(400);
        }

        $posts = new Posts($this->db);
        $post = $posts->getMeta($_POST['post_id']);
        if (!$post) {
            $this->throwJsonError(404, _('Post does not exist'));
        }

        if ($post->userId != $this->user->id && !$this->user->isMod) {
            $this->throwJsonError(403, _('This isn\'t your post!'));
        }

        if ($post->userId != $this->user->id) {
            // Log post deletions by mods
            $log = new Log($this->db);
            $log->write(Log::ACTION_ID_MOD_POST_DELETE, $this->user->id, $post->id);
        }

        $messageQueue = new MessageQueue();

        // Delete notifications about post replies
        $replied = $posts->getRepliedPosts($_POST['post_id']);
        if (!empty($replied)) {
            $messageQueue->send([
                'types' => UserNotifications::NOTIFICATION_TYPE_POST_REPLY,
                'posts' => $replied,
            ], MessageQueue::MSG_TYPE_REMOVE_POST_NOTIFICATION);
        }

        // Delete post
        $posts->delete($_POST['post_id']);
    }
}
