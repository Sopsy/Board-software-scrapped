<?php
namespace YBoard\Controller;

use YBoard\Controller;
use YBoard\Library\HttpResponse;
use YBoard\Library\TemplateEngine;

class BasicErrors extends Controller
{
    public function showException($title = false, $message = false, $auxMessage = false)
    {
        $view = new TemplateEngine(ROOT_PATH . '/YBoard/View/', 'BasicErrors');

        $msgFi = 'Sisäinen virhe: ES-norppa nukahti! Kokeilethan hetken päästä uudelleen...';
        $msgEn = 'Internal error: ES-seal fell asleep! Please try again in a moment...';

        $title = !empty($title) ? $title : 'Voihan pahus! - Oh noes!';
        $message = !empty($message) ? $message : $msgFi . ' - ' . $msgEn;

        $view->pageTitle = $view->errorTitle = $title;
        $view->errorMessage = $message;
        $view->auxMessage = $auxMessage;

        HttpResponse::setStatusCode(500);
        $view->display('Error');
    }
}
