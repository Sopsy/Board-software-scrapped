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
        $view = new TemplateEngine();

        $view->display('Index');
    }
}
