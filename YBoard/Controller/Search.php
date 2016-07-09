<?php
namespace YBoard\Controller;

use YBoard\Abstracts\ExtendedController;

class Search extends ExtendedController
{
    public function index()
    {
        $view = $this->loadTemplateEngine();

        $view->pageTitle = _('Search');

        $view->display('Search');
    }
}
