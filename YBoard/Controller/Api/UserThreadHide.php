<?php
namespace YBoard\Controller\Api;

use YBoard\ApiController;
Use YBoard\Model;

class UserThreadHide extends ApiController
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

        if (!$this->user->threadIsHidden($_POST['threadId'])) {
            Model\UserThreadHide::create($this->db, $this->user->id, $_POST['threadId']);
            $thread->updateStats('hideCount');
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

        $hiddenThread = $this->user->getHiddenThread($_POST['threadId']);
        if ($hiddenThread !== null) {
            $hiddenThread->delete();
            $thread->updateStats('hideCount', -1);
        }
    }
}
