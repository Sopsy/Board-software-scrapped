<?php
namespace YFW\Library;

class ReCaptcha
{
    public static function verify(string $response, string $secret): bool
    {
        $url = 'https://www.google.com/recaptcha/api/siteverify';
        $data = [
            'secret' => $secret,
            'response' => $response,
            'remoteip' => $_SERVER['REMOTE_ADDR'],
        ];

        $context = stream_context_create([
            'http' => [
                'header' => "Content-type: application/x-www-form-urlencoded\r\n",
                'method' => 'POST',
                'content' => http_build_query($data),
            ],
        ]);
        $result = file_get_contents($url, false, $context);
        if (!$result) {
            return false;
        }

        $result = json_decode($result);
        if (!empty($result->success) && $result->success) {
            return true;
        }

        return false;
    }
}
