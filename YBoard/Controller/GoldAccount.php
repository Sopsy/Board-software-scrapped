<?php
namespace YBoard\Controller;

use YBoard\Abstracts\ExtendedController;

class GoldAccount extends ExtendedController
{
    public function index()
    {
        $view = $this->loadTemplateEngine();

        $view->display('Gold');
    }
}
