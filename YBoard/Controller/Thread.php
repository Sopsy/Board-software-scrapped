<?php
namespace YBoard\Controller;

use YBoard\Controller;
use YBoard\Model;
use YFW\Library\Cache;
use YFW\Library\HttpResponse;

class Thread extends Controller
{
    public function index(string $boardUrl, int $threadId): void
    {
        // Get thread
        $thread = Model\Thread::get($this->db, $threadId);
        if ($thread === null) {
            // Does not exist. Was it deleted?
            $deleted = Model\Post::getDeleted($this->db, $threadId);
            if (!$deleted) {
                $this->notFound(null, _('The thread you are looking for does not exist.'));
            } else {
                $this->gone(null, _('The thread you are looking for has been deleted.'));
            }
        }

        // Get board
        $board = Model\Board::getById($this->db, $thread->boardId);
        if ($boardUrl != $board->url) {
            // Invalid board for current thread, redirect
            HttpResponse::redirectExit('/' . $board->url . '/' . $thread->id);
        }

        // Clear unread count and update last seen reply
        if ($this->user->threadIsFollowed($thread->id)) {
            $followedThread = $this->user->getFollowedThread($thread->id);
            if (!empty($thread->replies)) {
                $tmp = array_slice($thread->replies, -1);
                $lastReply = array_pop($tmp);
                $followedThread->setLastSeenReply($lastReply->id);
            }
            $this->user->markFollowedRead($thread->id);
        }

        // Increment thread views
        $viewCacheKey = 'thread-view-' . $thread->id . '-' . $_SERVER['REMOTE_ADDR'];
        if (!Cache::exists($viewCacheKey)) {
            Cache::add($viewCacheKey, 1, 300);
            $thread->updateStats('readCount');
        }

        $view = $this->loadTemplateEngine();
        $view->setVar('pageTitle', $thread->subject);
        $view->setVar('bodyClass', 'thread-page');
        $view->setVar('board', $board);
        $view->setVar('thread', $thread);

        $view->display('Thread');
    }
}
