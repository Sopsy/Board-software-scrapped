<?php
namespace YBoard\Controller;

use YBoard\Controller;

class Preferences extends Controller
{
    public function index(): void
    {
        $view = $this->loadTemplateEngine();

        $view->setVar('pageTitle', _('Preferences'));
        $view->display('Preferences');
    }
}
