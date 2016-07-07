<?php
namespace YBoard\Library;

class BbCode
{
    public static function strip($str)
    {
        // For performance
        if (strpos($str, '[') === false || strpos($str, ']') === false || strpos($str, '/') === false) {
            return $str;
        }

        return preg_replace('#\[[a-z/]+\]+#si', '$1', $str);
    }
}
