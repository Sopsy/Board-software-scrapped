<?php

namespace YBoard\Controller;

use YBoard\Library\HttpResponse;
use YBoard\Abstracts\ExtendedController;
use YBoard\Model;

class Board extends ExtendedController
{
    public function index($boardUrl, $pageNum = 1)
    {
        $view = $this->loadTemplateEngine();

        if (!$this->boards->exists($boardUrl)) {
            if ($this->boards->isAltUrl($boardUrl)) {
                HttpResponse::redirectExit('/' . $this->boards->getUrlByAltUrl($boardUrl) . '/', 302);
                // TODO: Change to 301 after everything works
            }
            // Board does not exist and no alt_url match
            $errorTitle = _('Board does not exist');
            $errorMessage = sprintf(_('No such thing as a board called "%s" exists here. Maybe you should try again with something else.'), $boardUrl);
            $this->notFound($errorTitle, $errorMessage);
        }

        $board = $this->boards->getByUrl($boardUrl);

        // Maybe we should get some posts too...

        $view->board = $board;
        $view->pageNum = $pageNum;
        $view->display('Board');
    }
}
