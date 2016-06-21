<?php

namespace YBoard\Controller;

use YBoard\Abstracts\ExtendedController;

class Index extends ExtendedController
{
    public function index()
    {
        $view = $this->loadTemplateEngine();

        $view->display('Index');
    }
}
