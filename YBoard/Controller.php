<?php

namespace YBoard;

abstract class Controller
{
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
}
