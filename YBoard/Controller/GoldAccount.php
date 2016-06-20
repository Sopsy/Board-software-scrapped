<?php
namespace YBoard\Controller;

use YBoard\Controller;

class GoldAccount extends Controller
{
    public function index(): void
    {
        $view = $this->loadTemplateEngine();
        $view->setVar('pageTitle', _('Gold account'));

        $view->display('Gold');
    }
}
