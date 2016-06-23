<?php

namespace YBoard\Model;

use YBoard\Library\Database;
use YBoard\Library\HttpResponse;
use YBoard\Library\Text;
use YBoard;

class User extends YBoard\Model
{

    public $id;
    public $username;
    public $userClass;
    public $csrfToken;
    public $ip;


    public function __construct(Database $dbConnection)
    {
        parent::__construct($dbConnection);

        if (!empty($_COOKIE['user'])) {
            $session = str_split($_COOKIE['user'], 32);

            if (count($session) != 2) {
                HttpResponse::setCookie('user', '', true);
                HttpResponse::redirectExit($_SERVER['REQUEST_URI']);
            }
        }
    }

    protected function verifyAnonymousSession($userId, $sessionId)
    {
        return $sessionId === $this->getAnonymousSessionId($userId);
    }

    protected function getAnonymousSessionId($userId)
    {
        return md5(hash('sha256', sha1($userId) . $this->sessionHashKey));
    }

    protected function maybeBot()
    {
        if (empty($_SERVER['HTTP_ACCEPT_LANGUAGE'])) // Great way of detecting crawlers!
        {
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

        return false;
    }

    private function create()
    {
        $this->username = Text::randomStr();
        $this->csrfToken = Text::randomStr();
        $this->ip = $_SERVER['REMOTE_ADDR'];

        $q = $this->db->prepare("INSERT INTO user_accounts (username, csrf_token, last_ip) VALUES (:username, :csrf_token, :last_ip)");
        $q->bindParam(':username', $this->username);
        $q->bindParam(':csrf_token', $this->csrfToken);
        $q->bindParam(':last_ip', inet_pton($this->ip));
        $q->execute();

        if (!$q) {
            return false;
        }

        $this->id = $this->db->lastInsertId();

        return true;
    }
}
