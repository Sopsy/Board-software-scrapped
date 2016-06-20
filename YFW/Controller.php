<?php
namespace YFW;

abstract class Controller
{
    public function __call(string $method, $args): void
    {
        if (!method_exists($this, $method)) {
            throw new \Exception('"Method ' . $method . ' does not exist in ' . get_class($this) . '"');
        }
    }

    protected function stopExecution(): void
    {
        die();
    }
}
