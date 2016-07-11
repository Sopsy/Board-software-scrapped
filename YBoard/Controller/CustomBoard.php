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
        $this->limitPages($pageNum, $this->config['view']['maxPages']);

        $posts = new Posts($this->db);

        $view = $this->loadTemplateEngine();
        $board = $this->getMyThreadsBoard();

        $view->pageTitle = $board->name;
        $view->bodyClass = 'board-page';

        $this->initializePagination($view, $pageNum, $this->config['view']['maxPages']);

        $threads = $posts->getThreadsByUser($this->user->id);
        $view->threads = $posts->getCustomThreads($threads, $pageNum, 15, 3);

        $view->board = $board;
        $view->pageNum = $pageNum;
        $view->display('Board');
    }

    public function myThreadsCatalog($pageNum = 1)
    {
        $this->limitPages($pageNum, $this->config['view']['maxCatalogPages']);

        $posts = new Posts($this->db);

        $view = $this->loadTemplateEngine();
        $board = $this->getMyThreadsBoard();

        $view->pageTitle = $board->name;
        $view->bodyClass = 'board-catalog';

        $this->initializePagination($view, $pageNum, $this->config['view']['maxCatalogPages'], '/catalog');

        $threads = $posts->getThreadsByUser($this->user->id);
        $view->threads = $posts->getCustomThreads($threads, $pageNum, 100, 0);

        $view->board = $board;
        $view->pageNum = $pageNum;
        $view->display('BoardCatalog');
    }

    public function repliedThreads($pageNum = 1)
    {
        $this->limitPages($pageNum, $this->config['view']['maxPages']);

        $posts = new Posts($this->db);

        $view = $this->loadTemplateEngine();
        $board = $this->getRepliedThreadsBoard();

        $view->pageTitle = $board->name;
        $view->bodyClass = 'board-page';

        $this->initializePagination($view, $pageNum, $this->config['view']['maxPages']);

        $threads = $posts->getThreadsRepliedByUser($this->user->id);
        $view->threads = $posts->getCustomThreads($threads, $pageNum, 15, 3);

        $view->board = $board;
        $view->pageNum = $pageNum;
        $view->display('Board');
    }

    public function repliedThreadsCatalog($pageNum = 1)
    {
        $this->limitPages($pageNum, $this->config['view']['maxCatalogPages']);

        $posts = new Posts($this->db);

        $view = $this->loadTemplateEngine();
        $board = $this->getRepliedThreadsBoard();

        $view->pageTitle = $board->name;
        $view->bodyClass = 'board-catalog';

        $this->initializePagination($view, $pageNum, $this->config['view']['maxCatalogPages'], '/catalog');

        $threads = $posts->getThreadsRepliedByUser($this->user->id);
        $view->threads = $posts->getCustomThreads($threads, $pageNum, 100, 0);

        $view->board = $board;
        $view->pageNum = $pageNum;
        $view->display('BoardCatalog');
    }

    protected function getMyThreadsBoard() : Board
    {
        $board = new Board();
        $board->name = _('My threads');
        $board->description = _('All the great threads you have created');
        $board->url = 'mythreads';

        return $board;
    }

    protected function getRepliedThreadsBoard() : Board
    {
        $board = new Board();
        $board->name = _('Replied threads');
        $board->description = _('Threads that may or may not have any interesting content');
        $board->url = 'repliedthreads';

        return $board;
    }
}
