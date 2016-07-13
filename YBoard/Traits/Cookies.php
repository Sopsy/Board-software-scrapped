<?php
namespace YBoard\Traits;

use YBoard\Library\HttpResponse;

trait Cookies
{
    protected function getLoginCookie()
    {
        if (empty($_COOKIE['user'])) {
            return false;
        }

        if (strlen($_COOKIE['user']) <= 65 || substr_count($_COOKIE['user'], '-') !== 1) {
            return false;
        }

        list($userId, $sessionId) = explode('-', $_COOKIE['user']);

        return ['userId' => (int)$userId, 'sessionId' => hex2bin($sessionId)];
    }

    protected function setLoginCookie(int $userId, $sessionId) : bool
    {
        $sessionId = bin2hex($sessionId);
        HttpResponse::setCookie('user', $userId . '-' . $sessionId);

        return true;
    }

    protected function deleteLoginCookie(bool $reload = false) : bool
    {
        HttpResponse::setCookie('user', '', false);
        if ($reload) {
            HttpResponse::redirectExit($_SERVER['REQUEST_URI']);
        }

        return true;
    }
}
