<?php
namespace YBoard\Controller;

use YBoard\Abstracts\ExtendedController;
use YBoard\Library\HttpResponse;
use YBoard\Model;

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

        $view = $this->loadTemplateEngine();

        $board = $this->boards->getByUrl($boardUrl);

        // TODO: Maybe we should get some posts too...

        $view->board = $board;
        $view->pageNum = $pageNum;
        $view->display('Board');
    }
}
