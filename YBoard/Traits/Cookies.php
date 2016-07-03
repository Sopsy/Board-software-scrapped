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

        if (strlen($_COOKIE['user']) !== 64) {
            return false;
        }

        $sessionId = hex2bin($_COOKIE['user']);

        return $sessionId;
    }

    protected function setLoginCookie($sessionId)
    {
        $sessionId = bin2hex($sessionId);
        HttpResponse::setCookie('user', $sessionId);

        return true;
    }

    protected function deleteLoginCookie($reload = false)
    {
        HttpResponse::setCookie('user', '', false);
        if ($reload) {
            HttpResponse::redirectExit($_SERVER['REQUEST_URI']);
        }

        return true;
    }
}
