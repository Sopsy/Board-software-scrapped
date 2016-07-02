<?php
namespace YBoard\Controller;

use YBoard\Abstracts\ExtendedController;
use YBoard\Library\HttpResponse;
use YBoard\Model\Posts;

class Thread extends ExtendedController
{
    public function index($boardUrl, $threadId)
    {
        $posts = new Posts($this->db);

        // Get thread
        $thread = $posts->getThread($threadId);
        if (!$thread) {
            $this->notFound(_('Not found'), _('The thread you are looking for does not exist.'));
        }

        // Get board
        $board = $this->boards->getById($thread->boardId);
        if ($boardUrl != $board->url) {
            // Invalid board for current thread, redirect
            HttpResponse::redirectExit('/' . $board->url . '/' . $thread->id);
            // TODO: Maybe change to 301
        }

        $view = $this->loadTemplateEngine();

        $view->board = $board;
        $view->thread = $thread;

        $view->display('Thread');
    }
}
