<?php

namespace YBoard\Controller;

use Library\HttpResponse;
use Library\TemplateEngine;
use YBoard\Abstracts\ExtendedController;
use YBoard\Model;

class Board extends ExtendedController
{
    public function index($boardUrl)
    {
        $view = $this->loadTemplateEngine();

        if ($this->boards->isAltUrl($boardUrl)) {
            HttpResponse::redirectExit('/' . $this->boards->getUrlByAltUrl($boardUrl) . '/', 302);
        }

        $board = $this->boards->getBoardByUrl($boardUrl);

        // Maybe we should get some posts too...

        $view->board = $board;
        $view->display('Board');
    }
}
