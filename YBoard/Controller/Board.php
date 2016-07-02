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
                // TODO: Change to 301 after everything works
            }
            // Board does not exist and no alt_url match
            $this->notFound(_('Not found'), sprintf(_('There\'s no such thing as a board called "%s" here.'), $boardUrl));
        }

        $posts = new Posts($this->db);
        
        $view = $this->loadTemplateEngine();

        $board = $this->boards->getByUrl($boardUrl);
        
        $view->threads = $posts->getBoardThreads($board->id, 10, 3);

        $view->board = $board;
        $view->pageNum = $pageNum;
        $view->display('Board');
    }
}
