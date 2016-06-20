<?php
namespace YFW\Library;

class Profiler
{
    protected static $startTime;

    public static function start(): void
    {
        static::$startTime = microtime(true);
    }

    public static function end(): string
    {
        return round((microtime(true) - static::$startTime) * 1000, 2) . ' ms';
    }
}
