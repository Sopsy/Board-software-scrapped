<?php

namespace YBoard;

use Library\HttpResponse;
use Library\TemplateEngine;

abstract class Controller
{
    protected $config;

    public function __call($method, $args)
    {
        if (!method_exists($this, $method)) {
            throw new \Exception('Method ' . $method . ' does not exist in ' . get_class($this) . '.');
        }
    }

    public function notFound()
    {
        HttpResponse::setStatusCode(404);
        $view = new TemplateEngine(ROOT_PATH . '/YBoard/Views/Templates/Default.phtml');

        $view->pageTitle = 'Sivua ei löydy';

        $view->display(ROOT_PATH . '/YBoard/Views/Pages/NotFound.phtml');
        $this->stopExecution();
    }

    protected function stopExecution()
    {
        die();
    }

    protected function blockAccess($pageTitle, $errorMessage)
    {
        $this->showMessage($pageTitle, $errorMessage, 403);
    }

    protected function badRequest($pageTitle, $errorMessage)
    {
        $this->showMessage($pageTitle, $errorMessage, 400);
    }

    protected function showMessage($errorTitle, $errorMessage, $httpStatus = false)
    {
        if ($httpStatus && is_int($httpStatus)) {
            HttpResponse::setStatusCode($httpStatus);
        }
        $view = new TemplateEngine(ROOT_PATH . '/YBoard/Views/Templates/Default.phtml');

        $view->pageTitle = $view->errorTitle = $errorTitle;
        $view->errorMessage = $errorMessage;

        $view->display(ROOT_PATH . '/YBoard/Views/Pages/Error.phtml');
        $this->stopExecution();
    }

    protected function loadConfig()
    {
        $this->config = require(ROOT_PATH . '/YBoard/Config/YBoard.php');
    }
}
