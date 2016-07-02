<?php
namespace YBoard\Controller;

use YBoard\Abstracts\ExtendedController;
use YBoard\Model\Posts;

class Post extends ExtendedController
{
    public function submit()
    {
        $this->validateAjaxCsrfToken();

        // Check bans
        if ($this->user->isBanned()) {
            $this->throwJsonError(403, _('You are banned...'));
        }

        $posts = new Posts($this->db);

        // Is this a reply or a new thread?
        if (empty($_POST['thread'])) {
            $isReply = false;
        } else {
            $isReply = true;
        }

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
            $thread = $posts->getThread($_POST['thread'], true);

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
        $sage = false;
        $hideName = false;
        $noCompress = false;
        $goldHide = false;

        if ((!empty($_POST['sage']) AND $_POST['sage'] == 'on')) {
            $sage = true;
        }
        if (!empty($_POST['hidename']) AND $_POST['hidename'] == 'on') {
            $hideName = true;
        }
        if (!empty($_POST['nocompress']) AND $_POST['nocompress'] == 'on' AND $this->user->hasGold) {
            $noCompress = true;
        }
        if (!empty($_POST['goldhide']) AND $_POST['goldhide'] == 'on' AND $this->user->hasGold) {
            $goldHide = true;
        }

        // Preprocess message
        if (isset($_POST['message'])) {
            $message = trim($_POST['message']);
        } else {
            $message = '';
        }

        // TODO: Check word blacklist

        $subject = null;
        if (!$isReply && isset($_POST['subject'])) {
            $subject = trim(mb_substr($_POST['subject'], 0, $this->config['posts']['subjectMaxLength']));
        }

        /*
        preg_match_all('/>>([0-9]+)/i', $message, $postReplies);
        $postReplies = array_unique($postReplies[1]);
        */


        // TODO: Check if username can be used at all
        $username = $this->user->username;

        if (!$isReply) {
            $postId = $posts->createThread($this->user->id, $board->id, $subject, $message, $username,
                $_SERVER['REMOTE_ADDR'], $countryCode);
        } else {
            $postId = $posts->addReply($this->user->id, $thread->id, $message, $username, $_SERVER['REMOTE_ADDR'], $countryCode);
        }

        // TODO: Bump thread
        // TODO: Update thread stats
        // TODO: Save replies
        // TODO: Save tags
        // TODO: Process uploaded file(s)
        // TODO: Add notifications

        $this->throwJsonError(400, $postId);
    }
}
