<?php

namespace YBoard\Controller;

use YBoard\Abstracts\ExtendedController;

class InfoPages extends ExtendedController
{
    public function index($url)
    {
        $view = $this->loadTemplateEngine();

        $view->pageTitle = $url;

        $view->display('Index');
    }
}
