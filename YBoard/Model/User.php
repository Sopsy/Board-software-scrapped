<?php
namespace YBoard\Model;

use YBoard\Exceptions\DatabaseException;
use YBoard\Model;

class User extends Model
{
    const PASSWORD_HASH_COST = 12;
    const PASSWORD_HASH_TYPE = PASSWORD_BCRYPT;

    public $id;
    public $sessionId;
    public $csrfToken;
    public $username;
    public $class;
    public $goldLevel;
    public $loggedIn;
    public $isMod = false;
    public $isAdmin = false;
    public $requireCaptcha = true;
    public $preferences;

    public function load($sessionId)
    {
        $q = $this->db->prepare("SELECT id, session_id, csrf_token, username, class, gold_level FROM user_sessions
            LEFT JOIN user_accounts ON id = user_id
            WHERE session_id = :sessionId LIMIT 1");
        $q->bindValue('sessionId', $sessionId);
        $q->execute();

        if ($q === false) {
            Throw new DatabaseException(_('Could not load your user account...'));
        }

        if ($q->rowCount() == 0) {
            return false;
        }

        $user = $q->fetch();
        $this->id = $user->id;
        $this->sessionId = $user->session_id;
        $this->csrfToken = bin2hex($user->csrf_token);
        $this->username = $user->username;
        $this->class = $user->class;
        $this->goldLevel = $user->gold_level;
        $this->loggedIn = empty($user->username) ? false : true;
        $this->preferences = new UserPreferences($this->db, $this->id);

        // TODO: Maybe change to sentPosts > n instead
        $this->requireCaptcha = !$this->loggedIn;

        if ($this->class == 1) {
            $this->isMod = true;
            $this->isAdmin = true;
        } elseif ($this->class == 2) {
            $this->isMod = true;
        }

        // Update last active -timestamp and IP-address
        $q = $this->db->prepare("UPDATE user_sessions SET last_active = NOW(), ip = :ip
            WHERE user_id = :userId AND session_id = :sessionId LIMIT 1");
        $q->bindValue('userId', (int)$this->id);
        $q->bindValue('sessionId', $this->sessionId);
        $q->bindValue('ip', inet_pton($_SERVER['REMOTE_ADDR']));
        $q->execute();

        if ($q === false) {
            Throw new DatabaseException(_('Could not refresh your user account...'));
        }

        return true;
    }

    public function update($key, $val, $userId = false)
    {
        if (!$userId) {
            $userId = $this->id;
        }

        if (empty($userId)) {
            return false;
        }

        if (!is_array($key) && is_array($val) || is_array($key) && !is_array($val)) {
            return false;
        }
        if (!is_array($key) && !is_array($val)) {
            $key = [$key];
            $val = [$val];
        }

        if (count($key) != count($val) || count($key) == 0) {
            return false;
        }

        $bind = [];
        $update = '';
        $i = 0;
        foreach ($key AS $curKey) {
            if ($curKey == 'lastIp') {
                $update .= 'last_ip = :lastIp,';
                $bind[$curKey] = inet_pton($val[$i]);
            } else {
                continue;
            }

            $this->$curKey = $val[$i];
            ++$i;
        }

        if (empty($update)) {
            return false;
        }

        // Remove last comma
        $update = substr($update, 0, -1);

        $q = $this->db->prepare("UPDATE user_accounts SET " . $update . " WHERE id = :userId LIMIT 1");
        $q->bindValue('userId', (int)$userId);
        foreach ($bind as $key => $val) {
            $q->bindValue($key, $val);
        }

        $q->execute();

        if ($q === false) {
            Throw new DatabaseException(_('Could not update the user account...'));
        }

        return true;
    }

    public function create()
    {
        $q = $this->db->query("INSERT INTO user_accounts VALUES ()");

        if ($q === false) {
            Throw new DatabaseException(_('Could not create an user account...'));
        }

        $this->id = $this->db->lastInsertId();

        return true;
    }

    public function delete($userId)
    {
        $q = $this->db->prepare("DELETE FROM user_accounts WHERE id = :userId LIMIT 1");
        $q->bindValue('userId', $userId);
        $q->execute();
        // Relations will handle the deletion of rest of the data, so we don't have to care.
        // Thank you relations!

        if ($q === false) {
            Throw new DatabaseException(_('Could not delete the user account...'));
        }

        return true;
    }

    public function validateLogin($username, $password)
    {
        $q = $this->db->prepare("SELECT id, username, password, class FROM user_accounts WHERE username = ? LIMIT 1");
        $q->execute([$username]);

        if ($q === false) {
            Throw new DatabaseException(_('Could not validate login info...'));
        }

        if ($q->rowCount() == 0) {
            return false;
        }

        $user = $q->fetch();

        if (empty($user->username)) {
            return false;
        }

        if (password_verify($password, $user->password)) {
            $this->id = $user->id;
            $this->class = $user->class;

            return true;
        }

        return false;
    }

    public function setPassword($newPassword, $userId = false)
    {
        if ($userId === false) {
            $userId = $this->id;
        }

        // Do note that this function does not verify old password!
        $newPassword = password_hash($newPassword, static::PASSWORD_HASH_TYPE, ['cost' => static::PASSWORD_HASH_COST]);

        $q = $this->db->prepare("UPDATE user_accounts SET password = :newPassword WHERE id = :userId LIMIT 1");
        $q->bindValue('newPassword', $newPassword);
        $q->bindValue('userId', $userId);
        $q->execute();

        if ($q === false) {
            Throw new DatabaseException(_('Could not set password for the user account...'));
        }

        return true;
    }

    public function setUsername($newUsername, $userId = false)
    {
        if ($userId === false) {
            $userId = $this->id;
        }

        $q = $this->db->prepare("UPDATE user_accounts SET username = :newUsername WHERE id = :userId LIMIT 1");
        $q->bindValue('newUsername', $newUsername);
        $q->bindValue('userId', $userId);
        $q->execute();

        if ($q === false) {
            Throw new DatabaseException(_('Could not set username for the user account...'));
        }

        return true;
    }

    public function createSession($userId)
    {
        $sessionId = random_bytes(32);
        $csrfToken = random_bytes(32);

        $q = $this->db->prepare("INSERT INTO user_sessions (user_id, session_id, csrf_token, ip) VALUES (:userId, :sessionId, :csrfToken, :ip)");
        $q->bindValue('userId', (int)$userId);
        $q->bindValue('sessionId', $sessionId);
        $q->bindValue('csrfToken', $csrfToken);
        $q->bindValue('ip', inet_pton($_SERVER['REMOTE_ADDR']));
        $q->execute();

        if ($q === false) {
            throw new DatabaseException(_('Could not create session for the user account..'));
        }

        $this->sessionId = $sessionId;
        $this->csrfToken = bin2hex($csrfToken);

        return true;
    }

    public function destroySession($sessionId = false)
    {
        if ($sessionId === false) {
            $sessionId = $this->sessionId;
        }

        $q = $this->db->prepare("DELETE FROM user_sessions WHERE session_id = :sessionId LIMIT 1");
        $q->bindValue('sessionId', $sessionId);
        $q->execute();

        if ($q === false) {
            throw new DatabaseException(_('Could not destroy the login session...'));
        }

        return true;
    }

    public function usernameIsFree($username)
    {
        $q = $this->db->prepare("SELECT id FROM user_accounts WHERE username LIKE :username LIMIT 1");
        $q->bindValue('username', $username);
        $q->execute();

        if ($q === false) {
            throw new DatabaseException(_('Could not check if the username is free...'));
        }

        if ($q->rowCount() == 0) {
            return true;
        }

        return false;
    }

    public function isBanned($ip = false, $userId = false)
    {
        if (!$ip) {
            $ip = $_SERVER['REMOTE_ADDR'];
        }
        if (!$userId) {
            $userId = $this->id;
        }

        $q = $this->db->prepare("SELECT id FROM bans WHERE ip = :ip OR user_id = :userId AND expired = 0 LIMIT 1");
        $q->bindValue('ip', inet_pton($ip));
        $q->bindValue('userId', $userId);
        $q->execute();

        if ($q->rowCount() >= 1) {
            return true;
        }

        return false;
    }

    public function getStatistics($key)
    {

    }
}
