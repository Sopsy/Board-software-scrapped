<?php
namespace YBoard\Controller;

use YBoard\Controller;

class Search extends Controller
{
    public function index(): void
    {
        $view = $this->loadTemplateEngine();

        $view->pageTitle = _('Search');

        $view->display('Search');
    }
}
