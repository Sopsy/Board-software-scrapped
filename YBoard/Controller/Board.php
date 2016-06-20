<?php
namespace YBoard\Controller;

use YBoard\Controller;
use YBoard\Model;
use YFW\Library\HttpResponse;
use YFW\Library\TemplateEngine;

class Board extends Controller
{
    public function index(string $boardUrl, int $negativePageNum = -1, int $pageNum = 1): void
    {
        $view = $this->loadView($boardUrl, $pageNum, false);
        $view->display('Board');
    }

    public function catalog(string $boardUrl, int $negativePageNum = -1, int $pageNum = 1): void
    {
        $view = $this->loadView($boardUrl, $pageNum, true);
        $view->display('BoardCatalog');
    }

    public function redirect(string $boardUrl, int $negativePageNum = -1, int $pageNum = 1): void
    {
        // Verify board exists
        $redirTo = false;
        if (Model\Board::exists($this->db, $boardUrl)) {
            $redirTo = Model\Board::getByUrl($this->db, $boardUrl)->url;
        } elseif (Model\Board::isAltUrl($this->db, $boardUrl)) {
            $redirTo = Model\Board::getUrlByAltUrl($this->db, $boardUrl);
        } else {
            $this->notFound(null, sprintf(_('There\'s no such thing as a board called "%s" here.'), $boardUrl));
        }

        if ($pageNum !== 1) {
            $redirTo .= '-' . $pageNum;
        }

        HttpResponse::redirectExit('/' . $redirTo . '/', 301);
    }

    protected function loadView(string $boardUrl, int $pageNum, bool $catalog): TemplateEngine
    {
        if (!Model\Board::exists($this->db, $boardUrl)) {
            if (Model\Board::isAltUrl($this->db, $boardUrl)) {
                HttpResponse::redirectExit('/' . Model\Board::getUrlByAltUrl($this->db, $boardUrl) . '/', 301);
            }
            // Board does not exist and no alt_url match
            $this->notFound(null, sprintf(_('There\'s no such thing as a board called "%s" here.'), $boardUrl));
        }

        $this->limitPages($pageNum,
            $catalog ? $this->config['view']['maxCatalogPages'] : $this->config['view']['maxPages']);

        Model\Thread::setHidden($this->user->getHiddenThreads());

        $board = Model\Board::getByUrl($this->db, $boardUrl);
        $threads = Model\Thread::getByBoard($this->db, $board->id, $pageNum,
            $catalog ? $this->user->preferences->threadsPerCatalogPage : $this->user->preferences->threadsPerPage,
            $catalog ? 0 : $this->user->preferences->repliesPerThread);

        $isLastPage = count($threads) < ($catalog ? $this->user->preferences->threadsPerCatalogPage : $this->user->preferences->threadsPerPage);

        $view = $this->loadTemplateEngine();

        $view->setVar('pageTitle', $board->name);
        $view->setVar('bodyClass', !$catalog ? 'board-page' : 'board-catalog-page');
        $view->setVar('threads', $threads);
        $view->setVar('board', $board);

        $this->initializePagination($view, $pageNum,
            $catalog ? $this->config['view']['maxCatalogPages'] : $this->config['view']['maxPages'], $isLastPage,
            $catalog);

        return $view;
    }
}
