<?php
namespace YFW\Library;

class Cache
{
    public static function add(string $key, $var, int $ttl = 0): bool
    {
        return apcu_add($key, $var, $ttl);
    }

    public static function delete(string $key): bool
    {
        return apcu_delete($key);
    }

    public static function exists(string $key): bool
    {
        return apcu_exists($key);
    }

    public static function fetch(string $key)
    {
        return apcu_fetch($key);
    }
}
