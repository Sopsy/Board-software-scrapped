<?php
namespace YBoard\Controller\Api;

use YBoard\ApiController;
use YBoard\Model;

class UserThreadFollow extends ApiController
{
    public function create(): void
    {
        if (empty($_POST['threadId'])) {
            $this->throwJsonError(400);
        }

        $thread = Model\Thread::get($this->db, $_POST['threadId'], false);
        if ($thread === null) {
            $this->throwJsonError(400, _('Thread does not exist'));
        }

        if (!$this->user->threadIsFollowed($_POST['threadId'])) {
            Model\UserThreadFollow::create($this->db, $this->user->id, $_POST['threadId']);
            $thread->updateStats('followCount');
        }
    }

    public function delete(): void
    {
        if (empty($_POST['threadId'])) {
            $this->throwJsonError(400);
        }

        $thread = Model\Thread::get($this->db, $_POST['threadId'], false);
        if ($thread === null) {
            $this->throwJsonError(400, _('Thread does not exist'));
        }

        $followedThread = $this->user->getFollowedThread($_POST['threadId']);
        if ($followedThread !== null) {
            $followedThread->delete();
            $thread->updateStats('followCount', -1);
        }
    }

    public function markAllRead(): void
    {
        Model\UserThreadFollow::markAllReadByUser($this->db, $this->user->id);
    }

    public function markRead(): void
    {
        if (empty($_POST['threadId'])) {
            $this->throwJsonError(400);
        }

        $followedThread = $this->user->getFollowedThread($_POST['threadId']);
        if ($followedThread !== null) {
            $followedThread->markRead();
        }
    }
}
