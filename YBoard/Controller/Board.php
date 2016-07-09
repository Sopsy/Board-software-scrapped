<?php
namespace YBoard\Controller;

use YBoard\Abstracts\ExtendedController;
use YBoard\Library\HttpResponse;
use YBoard\Model\Posts;

class Board extends ExtendedController
{
    public function index($boardUrl, $pageNum = 1)
    {
        // Verify board exists
        if (!$this->boards->exists($boardUrl)) {
            if ($this->boards->isAltUrl($boardUrl)) {
                HttpResponse::redirectExit('/' . $this->boards->getUrlByAltUrl($boardUrl) . '/', 302);
            }
            // Board does not exist and no alt_url match
            $this->notFound(_('Not found'), sprintf(_('There\'s no such thing as a board called "%s" here.'), $boardUrl));
        }

        if ($pageNum > $this->config['view']['maxPages']) {
            $this->notFound(_('Not found'), sprintf(_('Please don\'t. %s pages is enough.'), $this->config['view']['maxPages']));
        }

        $posts = new Posts($this->db);

        $view = $this->loadTemplateEngine();
        $board = $this->boards->getByUrl($boardUrl);

        $view->pageTitle = $board->name;

            // Calculate the end and start pages of the pagination
        // We don't count the total number of pages to save some resources.
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

    public function redirect($boardUrl)
    {
        // Verify board exists
        $redirTo = false;
        if ($this->boards->exists($boardUrl)) {
            $redirTo = $this->boards->getByUrl($boardUrl)->url;
        } elseif ($this->boards->isAltUrl($boardUrl)) {
            $redirTo = $this->boards->getUrlByAltUrl($boardUrl);
        }

        if ($redirTo) {
            HttpResponse::redirectExit('/' . $redirTo . '/', 302);
        }

        $this->notFound();
    }
}
