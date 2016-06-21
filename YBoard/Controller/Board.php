<?php

namespace YBoard\Controller;

use Library\HttpResponse;
use YBoard\Abstracts\ExtendedController;
use YBoard\Model;

class Board extends ExtendedController
{
    public function index($boardUrl)
    {
        $view = $this->loadTemplateEngine();

        if ($this->boards->isAltUrl($boardUrl)) {
            HttpResponse::redirectExit('/' . $this->boards->getUrlByAltUrl($boardUrl) . '/', 302);
            // TODO: Change to 301 after everything works
        }

        $board = $this->boards->getBoardByUrl($boardUrl);

        // Maybe we should get some posts too...

        $view->board = $board;
        $view->display('Board');
    }
}
