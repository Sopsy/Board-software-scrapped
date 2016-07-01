<?php

namespace YBoard\Controller;

use YBoard\Abstracts\ExtendedController;

class InfoPages extends ExtendedController
{
    public function faq()
    {
        $view = $this->loadTemplateEngine();

        $view->pageTitle = _('FAQ');

        $view->display('InfoPages/FAQ');
    }

    public function rules()
    {
        $view = $this->loadTemplateEngine();

        $view->pageTitle = _('Rules');

        $view->display('InfoPages/Rules');
    }

    public function about()
    {
        $view = $this->loadTemplateEngine();

        $view->pageTitle = _('About');

        $view->display('InfoPages/About');
    }

    public function advertising()
    {
        $view = $this->loadTemplateEngine();

        $view->pageTitle = _('Advertising');

        $view->display('InfoPages/Advertising');
    }
}
