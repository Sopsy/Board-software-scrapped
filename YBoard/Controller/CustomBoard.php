<?php
namespace YBoard\Controller;

use YBoard\Controller;
use YBoard\Model;

class CustomBoard extends Controller
{
    protected $board;
    protected $threadIds;

    public function index(string $fullName, string $shortName, int $negativePageNum = -1, int $pageNum = 1): void
    {
        $this->load($fullName, $shortName);

        $threads = Model\Thread::getCustom($this->db, $this->threadIds, $pageNum, $this->user->preferences->threadsPerPage,
            $this->user->preferences->repliesPerThread);

        $this->show($threads, $pageNum);
    }

    public function catalog(string $fullName, string $shortName, int $negativePageNum = -1, int $pageNum = 1): void
    {
        $this->load($fullName, $shortName);

        $threads = Model\Thread::getCustom($this->db, $this->threadIds, $pageNum, $this->user->preferences->threadsPerCatalogPage);

        $this->show($threads, $pageNum, true);
    }

    protected function load(string $fullName, string $shortName): void
    {
        Model\Thread::setHidden($this->user->getHiddenThreads());

        $this->board = new Model\Board($this->db);
        $this->board->url = $fullName;
        switch ($shortName) {
            case 'my':
                $this->board->name = _('My threads');
                $this->board->description = _('All the great threads you have created');
                $this->threadIds = Model\Thread::getIdsByUser($this->db, $this->user->id,
                    $this->user->preferences->threadsPerPage * $this->config['view']['maxPages']);
                break;
            case 'replied':
                $this->board->name = _('Replied threads');
                $this->board->description = _('Threads that may even have some interesting content');
                $this->threadIds = Model\Thread::getIdsRepliedByUser($this->db, $this->user->id,
                    $this->user->preferences->threadsPerPage * $this->config['view']['maxPages']);
                break;
            case 'followed':
                $this->board->name = _('Followed threads');
                $this->board->description = _('Threads you have marked as interesting');
                $this->threadIds = $this->user->getFollowedThreads();
                break;
            case 'hidden':
                $this->board->name = _('Hidden threads');
                $this->board->description = _('Why are you even reading these?');
                $this->threadIds = $this->user->getHiddenThreads();
                break;
            default:
                $this->notFound();
                die();
                break;
        }
    }

    protected function show(array $threads, int $pageNum, bool $catalog = false): void
    {
        if (!$catalog) {
            $maxPages = $this->config['view']['maxPages'];
            $bodyClass = 'board-page';
            $viewFile = 'Board';
            $isLastPage = count($threads) < $this->user->preferences->threadsPerPage;
        } else {
            $maxPages = $this->config['view']['maxCatalogPages'];
            $bodyClass = 'board-catalog';
            $viewFile = 'BoardCatalog';
            $isLastPage = count($threads) < $this->user->preferences->threadsPerCatalogPage;
        }

        $this->limitPages($pageNum, $maxPages);

        $view = $this->loadTemplateEngine();

        $view->setVar('pageTitle', $this->board->name);
        $view->setVar('bodyClass', $bodyClass);

        $this->initializePagination($view, $pageNum, $maxPages, $isLastPage, $catalog);

        $view->setVar('board', $this->board);
        $view->setVar('threads', $threads);
        $view->setVar('pageNum', $pageNum);

        $view->display($viewFile);
    }
}
