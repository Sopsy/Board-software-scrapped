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

    protected function stopExecution()
    {
        die();
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
