<?php
namespace YBoard\Controller;

use YBoard\Abstracts\ExtendedController;
use YBoard\Library\Cache;
use YBoard\Library\HttpResponse;
use YBoard\Library\MessageQueue;
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
        $view->pageTitle = $thread->subject;
        $view->bodyClass = 'thread-page';

        $view->board = $board;
        $view->thread = $thread;

        $view->display('Thread');
    }

    public function getReplies()
    {
        $this->validateAjaxCsrfToken();

        if (empty($_POST['thread_id']) || !isset($_POST['from_id'])) {
            $this->throwJsonError(400);
        }

        $newest = empty($_POST['newest']) ? false : true;

        $posts = new Posts($this->db);
        if ($posts->getThreadMeta($_POST['thread_id']) === false) {
            $this->throwJsonError(404, _('Thread does not exist'));
        }

        $replies = $posts->getReplies($_POST['thread_id'], null, $newest, $_POST['from_id']);

        $view = $this->loadTemplateEngine('Blank');

        $view->thread = $posts->getThreadMeta($_POST['thread_id']);
        $view->board = $this->boards->getById($view->thread->boardId);
        $view->tooltip = false;

        foreach ($replies as $post) {
            $view->post = $post;
            $view->display('Ajax/Post');
        }
    }

    public function hide()
    {
        $this->validateAjaxCsrfToken();

        if (empty($_POST['thread_id'])) {
            $this->throwJsonError(400);
        }

        $posts = new Posts($this->db);
        $posts->updateThreadStats($_POST['thread_id'], 'hideCount');

        $this->user->threadHide->add($_POST['thread_id']);
    }

    public function restore()
    {
        $this->validateAjaxCsrfToken();

        if (empty($_POST['thread_id'])) {
            $this->throwJsonError(400);
        }

        $posts = new Posts($this->db);
        $posts->updateThreadStats($_POST['thread_id'], 'hideCount', -1);

        $this->user->threadHide->remove($_POST['thread_id']);
    }

    public function follow()
    {
        $this->validateAjaxCsrfToken();

        if (empty($_POST['thread_id'])) {
            $this->throwJsonError(400);
        }

        $posts = new Posts($this->db);
        $posts->updateThreadStats($_POST['thread_id'], 'followCount');

        $this->user->threadFollow->add($_POST['thread_id']);
    }

    public function unfollow()
    {
        $this->validateAjaxCsrfToken();

        if (empty($_POST['thread_id'])) {
            $this->throwJsonError(400);
        }

        $posts = new Posts($this->db);
        $posts->updateThreadStats($_POST['thread_id'], 'followCount', -1);

        $this->user->threadFollow->remove($_POST['thread_id']);
    }
}
