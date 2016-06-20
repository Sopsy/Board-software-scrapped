<?php

namespace YBoard\Controller;

use Library\HttpResponse;
use Library\TemplateEngine;
use YBoard\Abstracts\ExtendedController;
use YBoard\Model;

class Index extends ExtendedController
{
    public function index()
    {
        $view = $this->loadTemplateEngine();

        $view->display('Index');
    }
}
