<?php
namespace YBoard\Controller;

use YBoard\Controller;
use YFW\Library\TemplateEngine;

class InfoPages extends Controller
{
    public function faq(): void
    {
        $view = $this->loadTemplateEngine();

        $view->setVar('pageTitle', _('FAQ'));
        $this->display($view, 'FAQ');
    }

    public function rules(): void
    {
        $view = $this->loadTemplateEngine();

        $view->setVar('pageTitle', _('Rules'));
        $this->display($view, 'Rules');
    }

    public function about(): void
    {
        $view = $this->loadTemplateEngine();

        $view->setVar('pageTitle', _('About'));
        $this->display($view, 'About');
    }

    public function advertising(): void
    {
        $view = $this->loadTemplateEngine();

        $view->setVar('pageTitle', _('Advertising'));
        $this->display($view, 'Advertising');
    }

    protected function display(TemplateEngine $view, string $pageName)
    {
        $view->display('InfoPage/' . $pageName);
    }
}
