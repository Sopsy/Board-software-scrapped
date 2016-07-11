<?php
namespace YBoard\Controller;

use YBoard\Abstracts\ExtendedController;
use YBoard\Library\HttpResponse;
use YBoard\Model\Posts;

class CustomBoard extends ExtendedController
{
    public function myThreads($pageNum)
    {
        if ($pageNum > $this->config['view']['maxPages']) {
            $this->notFound(_('Not found'), sprintf(_('Please don\'t. %s pages is enough.'), $this->config['view']['maxPages']));
        }

        $posts = new Posts($this->db);

        $view = $this->loadTemplateEngine();
        $board = $this->boards->getByUrl($boardUrl);

        $view->pageTitle = $board->name;
        $view->bodyClass = 'board-page';

        // Calculate the end and start pages of the pagination
        // We don't count the total number of pages to save some resources.
        $view->paginationBase = '';
        $view->maxPages = $this->config['view']['maxPages'];
        $view->paginationStartPage = $pageNum - 1;
        if ($view->paginationStartPage < 2) {
            $view->paginationStartPage = 2;
        }

        $view->paginationEndPage = $pageNum + 2;
        if ($view->paginationEndPage > $this->config['view']['maxPages']) {
            $view->paginationEndPage = $this->config['view']['maxPages'];
        }
        if ($view->paginationEndPage < 5) {
            $view->paginationEndPage = 5;
        }

        $view->threads = $posts->getBoardThreads($board->id, $pageNum, 15, 3);

        $view->board = $board;
        $view->pageNum = $pageNum;
        $view->display('Board');
    }

    public function repliedThreads($pageNum)
    {
        
    }
}
