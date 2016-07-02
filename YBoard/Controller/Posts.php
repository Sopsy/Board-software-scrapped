<?php
namespace YBoard\Controller;

use YBoard\Abstracts\ExtendedController;
use YBoard\Model;

class Posts extends ExtendedController
{
    public function submit()
    {
        $this->validateAjaxCsrfToken();

        // Check bans
        if ($this->user->isBanned()) {
            $this->throwJsonError(403, _('You are banned...'));
        }

        $postsModel = new Model\Posts($this->db);

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
        } else { // Replying to a thread
            $thread = $postsModel->getThread($_POST['thread']);

            // Verify thread
            if (!$thread) {
                $this->throwJsonError(400, _('Invalid thread'));
            }

            if ($thread->locked) {
                $this->throwJsonError(400, _('This thread is locked'));
            }
        }

        // TODO: Verify user can post to this board (locked?, mod only?)
        // TODO: Add flood prevention
        // TODO: Add CAPTCHA
        // TODO: Add country detection (ip2location?)

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

        $postSubject = NULL;
        if (!$isReply && isset($_POST['subject'])) {
            $postSubject = trim(mb_substr($postSubject, 0, $this->config['posts']['subjectMaxLength']));
        }

        /*
        preg_match_all('/>>([0-9]+)/i', $message, $postReplies);
        $postReplies = array_unique($postReplies[1]);
        */

        if (!$isReply) {
            $postId = $postsModel->createThread($postSubject, $message);
        } else {
            $postId = $postsModel->addReply($thread->id, $message);
        }

        // TODO: Save replies
        // TODO: Save tags
        // TODO: Process uploaded file(s)
        // TODO: Add notifications

        $this->throwJsonError(400, 'Ketä');
    }
}
