<?php
namespace YBoard\Controller\Api;

use YBoard\ApiController;
Use YBoard\Model;

class Thread extends ApiController
{
    public function getReplies(): void
    {
        if (empty($_POST['threadId']) || !isset($_POST['fromId'])) {
            $this->throwJsonError(400);
        }

        $newest = empty($_POST['newest']) ? false : true;
        $count = empty($_POST['count']) ? 100 : (int)$_POST['count'];
        $fromId = (int)$_POST['fromId'] === 0 ? null : (int)$_POST['fromId'];

        // Try to get the thread or throw an error if it does not exist
        $thread = $this->getThread($_POST['threadId']);

        $view = $this->loadTemplateEngine('Blank');

        $view->setVar('thread', $thread);
        $view->setVar('board', Model\Board::getById($this->db, $thread->boardId));
        $view->setVar('replies', $thread->getReplies($count, $newest, $fromId));

        $visibleReplies = !empty($_POST['visibleReplies']) ? explode(',', $_POST['visibleReplies']) : null;
        if ($visibleReplies !== null) {
            $deleted = $thread->getDeletedReplies($visibleReplies);
            if ($deleted !== null) {
                header('X-Deleted-Replies: ' . implode(',', $deleted));
            }
        }

        $view->display('Ajax/ThreadExpand');

        // Clear unread count and update last seen reply
        $followedThread = $this->user->getFollowedThread($_POST['threadId']);
        if ($followedThread !== null) {
            $this->user->markFollowedRead($_POST['threadId']);
            if ($fromId !== null) {
                $followedThread->setLastSeenReply($fromId);
            }
        }
    }
    protected function update(string $do, bool $bool): bool
    {
        $this->modOnly();

        if (empty($_POST['threadId'])) {
            $this->throwJsonError(400);
        }

        // Try to get the thread or throw an error if it does not exist
        $thread = $this->getThread($_POST['threadId']);

        if ($do == 'stick') {
            if ($bool) {
                return $thread->setSticky(true);
            } else {
                return $thread->setSticky(false);
            }
        } elseif ($do == 'lock') {
            if ($bool) {
                return $thread->setLocked(true);
            } else {
                return $thread->setLocked(false);
            }
        }

        return false;
    }

    protected function getThread(int $threadId): Model\Thread
    {
        $thread = Model\Thread::get($this->db, $threadId, false);
        if ($thread === null) {
            // The thread does not exist or was deleted
            $thread = Model\Post::getDeleted($this->db, $threadId);
            if ($thread !== null) {
                $this->throwJsonError(410, _('This thread has been deleted'));
            } else {
                $this->throwJsonError(404, _('Thread does not exist'));
            }
        }

        return $thread;
    }
}
