<?php

namespace YBoard\Model;

use YBoard\Library\Database;
use YBoard\Library\HttpResponse;
use YBoard;

class User extends YBoard\Model
{
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

    private function createProfile()
    {
        global $db, $engine;

        $username = $db->escape($engine->randString(8));
        $this->csrf_token = $csrf_token = $db->escape($engine->randString(8));
        $lastIp = $db->escape(inet_pton($this->getIp()));

        // Prevent cookieless spam
        /*
        $q = $db->q("SELECT * FROM user_accounts WHERE last_ip = '" . $lastIp . "' AND account_created > DATE_SUB(NOW(), INTERVAL 1 SECOND) LIMIT 1");
        if ($q->num_rows != 0) {
            return false;
        }
        */

        $q = $db->q("
			INSERT IGNORE INTO `user_accounts`
			(`username`, `csrf_token`, `last_ip`)
			VALUES ('" . $username . "', '" . $csrf_token . "', '" . $lastIp . "')
		");

        if ($q) {
            $this->id = $db->mysql0->insert_id;

            $this->updatePreferences('timezone', $this->getPreferredTimezone());
            $this->updatePreferences('language', $this->getPreferredLanguage());

            return $this->id;
        } else {
            return false;
        }

    }
}
