<?php
namespace YBoard\Controller;

use YBoard\Abstracts\ExtendedController;
use YBoard\Library\Cache;
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

        $viewCacheKey = 'thread-view-' . $thread->id . '-' . $_SERVER['REMOTE_ADDR'];
        if (!Cache::exists($viewCacheKey)) {
            Cache::add($viewCacheKey, 1, 300);
            $posts->updateThreadStats($thread->id, 'readCount');
        }

        $view = $this->loadTemplateEngine();
        // TODO: Set page title

        $view->board = $board;
        $view->thread = $thread;

        $view->display('Thread');
    }

    public function getReplies()
    {
        $this->validateAjaxCsrfToken();

        if (empty($_POST['threadId']) || !isset($_POST['fromId'])) {
            $this->throwJsonError(400);
        }

        $posts = new Posts($this->db);
        $replies = $posts->getReplies($_POST['threadId'], null, false, $_POST['fromId']);
        error_log($_POST['fromId']);

        $view = $this->loadTemplateEngine('Blank');

        $view->thread = $posts->getThreadMeta($_POST['threadId']);
        $view->board = $this->boards->getById($view->thread->boardId);

        foreach ($replies as $post) {
            $view->post = $post;
            $view->display('Ajax/Post');
        }
    }
}
