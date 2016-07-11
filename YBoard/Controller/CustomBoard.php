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
        $posts->setHiddenThreads($this->user->threadHide->threads);
        $threads = $posts->getThreadsByUser($this->user->id, 15*$this->config['view']['maxPages']);
        $threads = $posts->getCustomThreads($threads, $pageNum, 15, 3);
        $this->showCustomBoard($threads, 'mythreads', (int)$pageNum);
    }

    public function myThreadsCatalog($pageNum = 1)
    {
        $posts = new Posts($this->db);
        $posts->setHiddenThreads($this->user->threadHide->threads);
        $threads = $posts->getThreadsByUser($this->user->id, 100*$this->config['view']['maxCatalogPages']);
        $threads = $posts->getCustomThreads($threads, $pageNum, 100);
        $this->showCustomBoard($threads, 'mythreads', (int)$pageNum, true);
    }

    public function repliedThreads($pageNum = 1)
    {
        $posts = new Posts($this->db);
        $posts->setHiddenThreads($this->user->threadHide->threads);
        $threads = $posts->getThreadsRepliedByUser($this->user->id, 15*$this->config['view']['maxPages']);
        $threads = $posts->getCustomThreads($threads, $pageNum, 15, 3);
        $this->showCustomBoard($threads, 'repliedthreads', (int)$pageNum);
    }

    public function repliedThreadsCatalog($pageNum = 1)
    {
        $posts = new Posts($this->db);
        $posts->setHiddenThreads($this->user->threadHide->threads);
        $threads = $posts->getThreadsRepliedByUser($this->user->id, 100*$this->config['view']['maxCatalogPages']);
        $threads = $posts->getCustomThreads($threads, $pageNum, 100);
        $this->showCustomBoard($threads, 'repliedthreads', (int)$pageNum, true);
    }

    public function hiddenThreads($pageNum = 1)
    {
        $posts = new Posts($this->db);
        $threads = $posts->getCustomThreads($this->user->threadHide->threads, $pageNum, 15, 3);
        $this->showCustomBoard($threads, 'hiddenthreads', (int)$pageNum);
    }

    public function hiddenThreadsCatalog($pageNum = 1)
    {
        $posts = new Posts($this->db);
        $threads = $posts->getCustomThreads($this->user->threadHide->threads, $pageNum, 100);
        $this->showCustomBoard($threads, 'hiddenthreads', (int)$pageNum, true);
    }

    protected function showCustomBoard(array $threads, string $boardName, int $pageNum, bool $catalog = false)
    {
        if (!$catalog) {
            $maxPages = $this->config['view']['maxPages'];
            $bodyClass = 'board-page';
            $viewFile = 'Board';
            $paginationBase = '';
        } else {
            $maxPages = $this->config['view']['maxCatalogPages'];
            $bodyClass = 'board-catalog';
            $viewFile = 'BoardCatalog';
            $paginationBase = '/catalog';
        }
        

        $this->limitPages($pageNum, $maxPages);

        $view = $this->loadTemplateEngine();
        $board = $this->getCustomBoard($boardName);

        $view->pageTitle = $board->name;
        $view->bodyClass = $bodyClass;

        $this->initializePagination($view, $pageNum, $maxPages, $paginationBase);

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
                $board->description = _('Threads that may or may not have any interesting content');
                break;
            case 'hiddenthreads':
                $board->name = _('Hidden threads');
                $board->description = _('Why are you even reading these?');
                break;
        }

        return $board;
    }
}
