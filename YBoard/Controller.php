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
        $view = new TemplateEngine();

        $view->pageTitle = 'Sivua ei löydy';

        // Get a random 404-image
        $images = glob(ROOT_PATH . '/static/img/404/*.*');
        $view->imageSrc = $this->pathToUrl($images[array_rand($images)]);

        $view->display('NotFound');
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
        $view = new TemplateEngine();

        $view->pageTitle = $view->errorTitle = $errorTitle;
        $view->errorMessage = $errorMessage;

        $view->display('Error');
        $this->stopExecution();
    }

    protected function loadConfig()
    {
        $this->config = require(ROOT_PATH . '/YBoard/Config/YBoard.php');
    }

    protected function pathToUrl($path, $static = true) {
        if ($static) {
            $root = ROOT_PATH . '/static';
            $url = $this->config['app']['staticUrl'];
        } else {
            $root = ROOT_PATH . '/public';
            $url = $this->config['app']['baseUrl'];
        }

        return str_replace($root, $url, $path);
    }
}
