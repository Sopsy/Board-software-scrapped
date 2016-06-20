<?php
namespace YFW\Library;

class BotDetection
{
    protected static $botUserAgents;

    public static function setUserAgents(array $userAgents): void
    {
        static::$botUserAgents = $userAgents;
    }

    public static function isBot(?string $skipWithCookie = null, bool $checkUserAgents = true): bool
    {
        if ($skipWithCookie !== null && !empty($_COOKIE[$skipWithCookie])) {
            return false;
        }

        if (empty($_SERVER['HTTP_ACCEPT_LANGUAGE'])) {
            // Great way of detecting crawlers!
            return true;
        }

        if (empty($_SERVER['HTTP_USER_AGENT'])) {
            return true;
        }

        if (preg_match('/Baiduspider/i', $_SERVER['HTTP_USER_AGENT'])) {
            return true;
        }

        if (preg_match('/msnbot/i', $_SERVER['HTTP_USER_AGENT'])) {
            return true;
        }

        if ($checkUserAgents && in_array($_SERVER['HTTP_USER_AGENT'], static::$botUserAgents)) {
            return true;
        }

        return false;
    }
}
