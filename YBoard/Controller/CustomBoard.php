<?php
namespace YBoard\Controller;

use YBoard\Abstracts\ExtendedController;
use YBoard\Data\Board;
use YBoard\Library\HttpResponse;
use YBoard\Model\Posts;

class CustomBoard extends ExtendedController
{
    public function myThreads($pageNum = 1)
    {
        $posts = new Posts($this->db);
        $posts->setHiddenThreads($this->user->threadHide->getAll());
        $threads = $posts->getThreadsByUser($this->user->id,
            $this->user->preferences->threadsPerPage*$this->config['view']['maxPages']);
        $threads = $posts->getCustomThreads($threads, $pageNum, $this->user->preferences->threadsPerPage,
            $this->user->preferences->repliesPerThread);
        $this->showCustomBoard($threads, 'mythreads', (int)$pageNum);
    }

    public function repliedThreads($pageNum = 1)
    {
        $posts = new Posts($this->db);
        $posts->setHiddenThreads($this->user->threadHide->getAll());
        $threads = $posts->getThreadsRepliedByUser($this->user->id,
            $this->user->preferences->threadsPerPage*$this->config['view']['maxPages']);
        $threads = $posts->getCustomThreads($threads, $pageNum, $this->user->preferences->threadsPerPage,
            $this->user->preferences->repliesPerThread);
        $this->showCustomBoard($threads, 'repliedthreads', (int)$pageNum);
    }

    public function followedThreads($pageNum = 1)
    {
        // TODO: Add "mark all as read"
        $posts = new Posts($this->db);
        $threads = $posts->getCustomThreads(array_keys($this->user->threadFollow->getAll()), $pageNum,
            $this->user->preferences->threadsPerPage, $this->user->preferences->repliesPerThread, true);
        $this->showCustomBoard($threads, 'followedthreads', (int)$pageNum);
    }

    public function hiddenThreads($pageNum = 1)
    {
        $posts = new Posts($this->db);
        $threads = $posts->getCustomThreads($this->user->threadHide->getAll(), $pageNum,
            $this->user->preferences->threadsPerPage, $this->user->preferences->repliesPerThread);
        $this->showCustomBoard($threads, 'hiddenthreads', (int)$pageNum);
    }

    public function myThreadsCatalog($pageNum = 1)
    {
        $posts = new Posts($this->db);
        $posts->setHiddenThreads($this->user->threadHide->getAll());
        $threads = $posts->getThreadsByUser($this->user->id,
            $this->user->preferences->threadsPerCatalogPage*$this->config['view']['maxCatalogPages']);
        $threads = $posts->getCustomThreads($threads, $pageNum, $this->user->preferences->threadsPerCatalogPage);
        $this->showCustomBoard($threads, 'mythreads', (int)$pageNum, true);
    }

    public function repliedThreadsCatalog($pageNum = 1)
    {
        $posts = new Posts($this->db);
        $posts->setHiddenThreads($this->user->threadHide->getAll());
        $threads = $posts->getThreadsRepliedByUser($this->user->id,
            $this->user->preferences->threadsPerCatalogPage*$this->config['view']['maxCatalogPages']);
        $threads = $posts->getCustomThreads($threads, $pageNum, $this->user->preferences->threadsPerCatalogPage);
        $this->showCustomBoard($threads, 'repliedthreads', (int)$pageNum, true);
    }

    public function followedThreadsCatalog($pageNum = 1)
    {
        $posts = new Posts($this->db);
        $threads = $posts->getCustomThreads(array_keys($this->user->threadFollow->getAll()), $pageNum,
            $this->user->preferences->threadsPerCatalogPage);
        $this->showCustomBoard($threads, 'followedthreads', (int)$pageNum, true);
    }

    public function hiddenThreadsCatalog($pageNum = 1)
    {
        $posts = new Posts($this->db);
        $threads = $posts->getCustomThreads($this->user->threadHide->getAll(), $pageNum,
            $this->user->preferences->threadsPerCatalogPage);
        $this->showCustomBoard($threads, 'hiddenthreads', (int)$pageNum, true);
    }

    protected function showCustomBoard(array $threads, string $boardName, int $pageNum, bool $catalog = false)
    {
        if (!$catalog) {
            $maxPages = $this->config['view']['maxPages'];
            $bodyClass = 'board-page';
            $viewFile = 'Board';
            $paginationBase = '';
            $isLastPage = count($threads) < $this->user->preferences->threadsPerPage;
        } else {
            $maxPages = $this->config['view']['maxCatalogPages'];
            $bodyClass = 'board-catalog';
            $viewFile = 'BoardCatalog';
            $paginationBase = '/catalog';
            $isLastPage = count($threads) < $this->user->preferences->threadsPerCatalogPage;
        }

        $this->limitPages($pageNum, $maxPages);

        $view = $this->loadTemplateEngine();
        $board = $this->getCustomBoard($boardName);

        $view->pageTitle = $board->name;
        $view->bodyClass = $bodyClass;

        $this->initializePagination($view, $pageNum, $maxPages, $isLastPage, $paginationBase);

        $view->board = $board;
        $view->threads = $threads;
        $view->pageNum = $pageNum;
        $view->display($viewFile);
    }

    protected function getCustomBoard(string $name) : Board
    {
        $board = new Board();
        $board->url = $name;
        switch ($name) {
            case 'mythreads':
                $board->name = _('My threads');
                $board->description = _('All the great threads you have created');
                break;
            case 'repliedthreads':
                $board->name = _('Replied threads');
                $board->description = _('Threads that may even have some interesting content');
                break;
            case 'followedthreads':
                $board->name = _('Followed threads');
                $board->description = _('Threads you have marked as interesting');
                break;
            case 'hiddenthreads':
                $board->name = _('Hidden threads');
                $board->description = _('Why are you even reading these?');
                break;
        }

        return $board;
    }
}
