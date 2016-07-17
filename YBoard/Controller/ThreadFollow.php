<?php
namespace YBoard\Controller;

use YBoard\Abstracts\ExtendedController;
use YBoard\Model\Posts;

class ThreadFollow extends ExtendedController
{
    public function add()
    {
        $this->validateAjaxCsrfToken();

        if (empty($_POST['thread_id'])) {
            $this->throwJsonError(400);
        }

        $posts = new Posts($this->db);
        $thread = $posts->getThread($_POST['thread_id'], false);
        $thread->updateStats('followCount');

        $followedThread = $this->user->threadFollow->get($_POST['thread_id']);
        if ($followedThread === false) {
            $this->user->threadFollow->add($_POST['thread_id']);
        }
    }

    public function remove()
    {
        $this->validateAjaxCsrfToken();

        if (empty($_POST['thread_id'])) {
            $this->throwJsonError(400);
        }

        $posts = new Posts($this->db);
        $thread = $posts->getThread($_POST['thread_id'], false);
        $thread->updateStats('followCount', -1);

        $followedThread = $this->user->threadFollow->get($_POST['thread_id']);
        if ($followedThread !== false) {
            $followedThread->remove();
        }
    }

    public function markAllRead()
    {
        $this->validateAjaxCsrfToken();

        $this->user->threadFollow->markAllRead();
    }
}
