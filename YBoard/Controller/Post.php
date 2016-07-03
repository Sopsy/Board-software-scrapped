<?php
namespace YBoard\Controller;

use YBoard\Abstracts\ExtendedController;
use YBoard\Exceptions\FileUploadException;
use YBoard\Library\Text;
use YBoard\Model\Files;
use YBoard\Model\Posts;
use YBoard\Model\WordBlacklist;

class Post extends ExtendedController
{
    public function submit()
    {
        $this->validateAjaxCsrfToken();

        // Check bans
        if ($this->user->isBanned()) {
            $this->throwJsonError(403, _('You are banned!'));
        }

        $posts = new Posts($this->db);

        // Is this a reply or a new thread?
        $isReply = empty($_POST['thread']) ? false : true;

        if (!$isReply) { // Creating a new thread
            // Verify board
            if (empty($_POST['board']) || !$this->boards->exists($_POST['board'])) {
                $this->throwJsonError(400, _('Invalid board'));
            }

            // Message is required for new threads
            if (!isset($_POST['message'])) {
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
        }

        // TODO: Verify user can post to this board (locked?, mod only?)
        // TODO: Add flood prevention
        // TODO: Add CAPTCHA

        // TODO: Add country detection (ip2location?)
        $countryCode = 'FI';

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

        // TODO: Check if username can be used at all
        $username = null;
        if (!$hideName) {
            $username = $this->user->username;
        }

        // Check that we have enough free space for files
        if (disk_free_space($this->config['files']['savePath']) <= $this->config['files']['diskMinFree']) {
            $this->throwJsonError(400, _('File uploads are temporarily disabled'));
        }

        // Process file
        $hasFile = !empty($_FILES['files']) ? true : false;
        if ($hasFile) {
            $files = new Files($this->db);
            $files->setConfig($this->config['files']);

            // TODO: Verify file types are allowed
            // TODO: Limit file size
            try {
                $file = $files->processUpload($_FILES['files']);
            } catch (FileUploadException $e) {
                $this->throwJsonError(500, $e->getMessage());
            }
        }

        if (!$isReply) {
            $postId = $posts->createThread($this->user->id, $board->id, $subject, $message, $username,
                $_SERVER['REMOTE_ADDR'], $countryCode);
        } else {
            $postId = $posts->addReply($this->user->id, $thread->id, $message, $username, $_SERVER['REMOTE_ADDR'],
                $countryCode);

            if (!$sage) {
                $posts->bumpThread($thread->id);
            }
        }

        if ($hasFile) {
            $posts->addFileToPost($postId, $file->id, $file->origName);
        }

        // TODO: Save uploaded file
        // TODO: Update thread stats

        // TODO: Save replies
        /*
        preg_match_all('/>>([0-9]+)/i', $message, $postReplies);
        $postReplies = array_unique($postReplies[1]);
        */

        // TODO: Save tags
        // TODO: Add notifications

        $this->throwJsonError(400, $postId);
    }
}
