<?php
namespace YFW\Library;

class HttpResponse
{
    public static function redirectExit(string $url, int $type = 302): void
    {
        if (!in_array($type, [301, 302, 303, 307, 308])) {
            $type = 302;
        }
        static::setStatusCode($type);
        header('Location: ' . $url);
        die();
    }

    public static function setCookie(string $name, string $value, ?int $ttlDays = 365): bool
    {
        if ($ttlDays !== null) {
            $expire = time() + ((int)$ttlDays * 86400);
        } else {
            $expire = 1;
        }

        return setcookie($name, $value, $expire, '/', null, isset($_SERVER['HTTPS']), true) !== false;
    }

    public static function setStatusCode(int $statusCode, ?array $additionalHeaders = null): void
    {
        if (!isset($statusCode) || !is_numeric($statusCode)) {
            throw new \InvalidArgumentException('Invalid status code ' . $statusCode . ' for method ' . __METHOD__);
        }

        $fail = false;
        switch ($statusCode) {
            case 200:
                header($_SERVER["SERVER_PROTOCOL"] . ' 200 OK');
                break;
            case 201:
                header($_SERVER["SERVER_PROTOCOL"] . ' 201 Created');
                break;
            case 202:
                header($_SERVER["SERVER_PROTOCOL"] . ' 202 Accepted');
                break;
            case 204:
                header($_SERVER["SERVER_PROTOCOL"] . ' 204 No Content');
                break;
            case 301:
                header($_SERVER["SERVER_PROTOCOL"] . ' 301 Moved Permanently');
                break;
            case 302:
                header($_SERVER["SERVER_PROTOCOL"] . ' 302 Found');
                break;
            case 303:
                header($_SERVER["SERVER_PROTOCOL"] . ' 303 See Other');
                break;
            case 304:
                header($_SERVER["SERVER_PROTOCOL"] . ' 304 Not Modified');
                break;
            case 400:
                header($_SERVER["SERVER_PROTOCOL"] . ' 400 Bad Request');
                break;
            case 401:
                header($_SERVER["SERVER_PROTOCOL"] . ' 401 Unauthorized');
                break;
            case 403:
                header($_SERVER["SERVER_PROTOCOL"] . ' 403 Permission Denied');
                break;
            case 404:
                header($_SERVER["SERVER_PROTOCOL"] . ' 404 Not Found');
                break;
            case 405:
                header($_SERVER["SERVER_PROTOCOL"] . ' 405 Method Not Allowed');
                break;
            case 410:
                header($_SERVER["SERVER_PROTOCOL"] . ' 410 Gone');
                break;
            case 418:
                header($_SERVER["SERVER_PROTOCOL"] . ' 418 I\'m a teapot');
                break;
            case 500:
                header($_SERVER["SERVER_PROTOCOL"] . ' 500 Internal Server Error');
                break;
            case 501:
                header($_SERVER["SERVER_PROTOCOL"] . ' 501 Not Implemented');
                break;
            case 503:
                header($_SERVER["SERVER_PROTOCOL"] . ' 503 Service Unavailable');
                break;
            default:
                $fail = true;
                break;
        }

        if ($additionalHeaders !== null) {
            foreach ($additionalHeaders AS $header => $value) {
                header($header . ': ' . $value);
            }
        }

        if ($fail) {
            throw new \InvalidArgumentException('Unsupported or invalid status code for method ' . __METHOD__);
        }
    }
}
