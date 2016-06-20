<?php
namespace YBoard\Controller;

use YBoard\Controller;

class Index extends Controller
{
    public function index(): void
    {
        $view = $this->loadTemplateEngine();

        $view->display('Index');
    }
}
