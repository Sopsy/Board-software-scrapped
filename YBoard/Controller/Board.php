<?php
namespace YBoard\Controller;

use YBoard\Abstracts\ExtendedController;
use YBoard\Library\HttpResponse;
use YBoard\Model\Posts;

class Board extends ExtendedController
{
    public function index($boardUrl, $pageNum = 1)
    {
        $this->verifyBoard($boardUrl);

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

    public function catalog($boardUrl, $pageNum = 1)
    {
        $this->verifyBoard($boardUrl);

        if ($pageNum > $this->config['view']['maxCatalogPages']) {
            $this->notFound(_('Not found'), sprintf(_('Please don\'t. %s pages is enough.'), $this->config['view']['maxCatalogPages']));
        }

        $posts = new Posts($this->db);

        $view = $this->loadTemplateEngine();
        $board = $this->boards->getByUrl($boardUrl);

        $view->pageTitle = $board->name;
        $view->bodyClass = 'board-catalog';

        // Calculate the end and start pages of the pagination
        // We don't count the total number of pages to save some resources.
        $view->paginationBase = '/catalog';
        $view->paginationStartPage = $pageNum - 1;
        if ($view->paginationStartPage < 2) {
            $view->paginationStartPage = 2;
        }

        $view->paginationEndPage = $pageNum + 2;
        if ($view->paginationEndPage > $this->config['view']['maxCatalogPages']) {
            $view->paginationEndPage = $this->config['view']['maxCatalogPages'];
        }
        if ($view->paginationEndPage < 5) {
            $view->paginationEndPage = 5;
        }

        $view->threads = $posts->getBoardThreads($board->id, $pageNum, 100, 0);

        $view->board = $board;
        $view->pageNum = $pageNum;
        $view->display('BoardCatalog');
    }

    public function redirect($boardUrl, $boardPage = 1, $catalog = '', $catalogPage = 1)
    {
        // Verify board exists
        $redirTo = false;
        if ($this->boards->exists($boardUrl)) {
            $redirTo = $this->boards->getByUrl($boardUrl)->url;
        } elseif ($this->boards->isAltUrl($boardUrl)) {
            $redirTo = $this->boards->getUrlByAltUrl($boardUrl);
        }

        if (!empty($catalog)) {
            $redirTo .= '/catalog';
        }

        if ($redirTo) {
            HttpResponse::redirectExit('/' . $redirTo . '/', 302);
        }

        $this->notFound();
    }

    protected function verifyBoard($boardUrl)
    {
        if (!$this->boards->exists($boardUrl)) {
            if ($this->boards->isAltUrl($boardUrl)) {
                HttpResponse::redirectExit('/' . $this->boards->getUrlByAltUrl($boardUrl) . '/', 302);
            }
            // Board does not exist and no alt_url match
            $this->notFound(_('Not found'), sprintf(_('There\'s no such thing as a board called "%s" here.'), $boardUrl));
        }
    }
}
