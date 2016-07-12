<?php
namespace YBoard\Model;

use YBoard\Data\UserSession;
use YBoard\Exceptions\UserException;
use YBoard\Library\Database;
use YBoard\Library\Text;
use YBoard\Model;

class User extends Model
{
    const PASSWORD_HASH_COST = 12;
    const PASSWORD_HASH_TYPE = PASSWORD_BCRYPT;

    public $id;
    public $sessionId;
    public $accountCreated;
    public $csrfToken;
    public $username;
    public $class;
    public $goldLevel;
    public $preferences;
    public $statistics;
    public $threadHide;
    public $lastActive;
    public $lastIp;
    public $loggedIn = false;
    public $isMod = false;
    public $isAdmin = false;
    public $requireCaptcha = true;

    public function load($sessionId)
    {
        $q = $this->db->prepare("SELECT a.session_id, a.csrf_token, b.id, b.username, b.class, b.gold_level, b.account_created,
            b.last_active, b.last_ip
            FROM user_sessions a
            LEFT JOIN users b ON b.id = a.user_id
            WHERE a.session_id = :session_id LIMIT 1");
        $q->bindValue('session_id', $sessionId);
        $q->execute();

        if ($q->rowCount() == 0) {
            return false;
        }

        $user = $q->fetch();
        $this->id = $user->id;
        $this->accountCreated = $user->account_created;
        $this->sessionId = $user->session_id;
        $this->csrfToken = bin2hex($user->csrf_token);
        $this->username = $user->username;
        $this->class = $user->class;
        $this->goldLevel = $user->gold_level;
        $this->lastActive = $user->last_active;
        $this->lastIp = empty($user->last_ip) ? false : inet_ntop($user->last_ip);
        $this->loggedIn = empty($user->username) ? false : true;

        $this->loadSubclasses();

        // TODO: Maybe change to sentPosts > n instead
        $this->requireCaptcha = !$this->loggedIn;

        if ($this->class == 1) {
            $this->isMod = true;
            $this->isAdmin = true;
        } elseif ($this->class == 2) {
            $this->isMod = true;
        }

        // Update last active -timestamp and IP-address for session and user
        $q = $this->db->prepare("UPDATE user_sessions SET last_active = NOW(), ip = :ip
            WHERE user_id = :user_id AND session_id = :session_id LIMIT 1");
        $q->bindValue('user_id', (int)$this->id);
        $q->bindValue('session_id', $this->sessionId);
        $q->bindValue('ip', inet_pton($_SERVER['REMOTE_ADDR']));
        $q->execute();

        $q = $this->db->prepare("UPDATE users SET last_active = NOW(), last_ip = :last_ip WHERE id = :user_id LIMIT 1");
        $q->bindValue('user_id', (int)$this->id);
        $q->bindValue('last_ip', inet_pton($_SERVER['REMOTE_ADDR']));
        $q->execute();

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

        $q = $this->db->prepare("UPDATE users SET " . $update . " WHERE id = :user_id LIMIT 1");
        $q->bindValue('user_id', (int)$userId);
        foreach ($bind as $key => $val) {
            $q->bindValue($key, $val);
        }

        $q->execute();

        return true;
    }

    public function createTemporary()
    {
        $this->id = false;
        $this->loadSubclasses(true);
    }

    public function create()
    {
        $q = $this->db->query("INSERT INTO users VALUES ()");

        $this->id = $this->db->lastInsertId();
        $this->loadSubclasses(true);

        return true;
    }

    protected function loadSubclasses(bool $skipDbLoad = false) : bool
    {
        $this->preferences = new UserPreferences($this->db, $this->id, $skipDbLoad);
        $this->statistics = new UserStatistics($this->db, $this->id, $skipDbLoad);
        $this->threadHide = new UserThreadHide($this->db, $this->id, $skipDbLoad);

        return true;
    }

    public function delete(int $userId, string $password, bool $skipPasswordCheck = false) : bool
    {
        if (!$skipPasswordCheck) {
            $q = $this->db->prepare("SELECT password FROM users WHERE id = :user_id LIMIT 1");
            $q->bindValue('user_id', $userId);
            $q->execute();

            if ($q->rowCount() == 0) {
                throw new UserException(_('Invalid user'));
            }

            $user = $q->fetch();
            if (!password_verify($password, $user->password)) {
                throw new UserException(_('Invalid password'));
            }
        }

        // Relations will handle the deletion of rest of the data, so we don't have to care.
        // Thank you relations!
        $q = $this->db->prepare("DELETE FROM users WHERE id = :user_id LIMIT 1");
        $q->bindValue('user_id', $userId);
        $q->execute();

        return true;
    }

    public function validateLogin($username, $password)
    {
        $q = $this->db->prepare("SELECT id, username, password, class FROM users WHERE username = :username LIMIT 1");
        $q->bindValue('username', $username);
        $q->execute();

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

        $q = $this->db->prepare("UPDATE users SET password = :new_password WHERE id = :user_id LIMIT 1");
        $q->bindValue('new_password', $newPassword);
        $q->bindValue('user_id', $userId);
        $q->execute();

        return true;
    }

    public function setUsername($newUsername, $userId = false)
    {
        if ($userId === false) {
            $userId = $this->id;
        }

        $q = $this->db->prepare("UPDATE users SET username = :new_username WHERE id = :user_id LIMIT 1");
        $q->bindValue('new_username', $newUsername);
        $q->bindValue('user_id', $userId);
        $q->execute();

        return true;
    }

    public function createSession($userId)
    {
        $sessionId = random_bytes(32);
        $csrfToken = random_bytes(32);

        $q = $this->db->prepare("INSERT INTO user_sessions (user_id, session_id, csrf_token, ip) VALUES (:user_id, :session_id, :csrf_token, :ip)");
        $q->bindValue('user_id', (int)$userId);
        $q->bindValue('session_id', $sessionId);
        $q->bindValue('csrf_token', $csrfToken);
        $q->bindValue('ip', inet_pton($_SERVER['REMOTE_ADDR']));
        $q->execute();

        $this->sessionId = $sessionId;
        $this->csrfToken = bin2hex($csrfToken);

        return true;
    }

    public function getSessions(int $userId) : array
    {
        $q = $this->db->prepare("SELECT session_id, user_id, csrf_token, ip, login_time, last_active
            FROM user_sessions WHERE user_id = :user_id");
        $q->bindValue('user_id', $userId);
        $q->execute();

        $sessions = [];
        while ($row = $q->fetch()) {
            $tmp = new UserSession();
            $tmp->id = $row->session_id;
            $tmp->userId = $row->user_id;
            $tmp->csrfToken = bin2hex($row->csrf_token);
            $tmp->ip = inet_ntop($row->ip);
            $tmp->loginTime = $row->login_time;
            $tmp->lastActive = $row->last_active;
            $sessions[] = $tmp;
        }

        return $sessions;
    }

    public function destroySession($sessionId = false, $userId = false)
    {
        if ($sessionId === false) {
            $sessionId = $this->sessionId;
        }

        $whereUser = '';
        if ($userId !== false) {
            $whereUser = ' AND user_id = :user_id';
        }

        $q = $this->db->prepare("DELETE FROM user_sessions WHERE session_id = :session_id" . $whereUser . " LIMIT 1");
        $q->bindValue('session_id', $sessionId);
        if ($userId !== false) {
            $q->bindValue('user_id', (int)$userId);
        }
        $q->execute();

        return true;
    }

    public function usernameIsFree($username)
    {
        $q = $this->db->prepare("SELECT id FROM users WHERE username LIKE :username LIMIT 1");
        $q->bindValue('username', $username);
        $q->execute();

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

        $q = $this->db->prepare("SELECT id FROM bans WHERE ip = :ip OR user_id = :user_id AND is_expired = 0 LIMIT 1");
        $q->bindValue('ip', inet_pton($ip));
        $q->bindValue('user_id', $userId);
        $q->execute();

        if ($q->rowCount() >= 1) {
            return true;
        }

        return false;
    }

    // Get user accounts that have no active sessions and cannot be logged in to
    public function getUnusable() : array
    {
        $q = $this->db->query("SELECT a.id FROM users a
            LEFT JOIN user_sessions b ON b.user_id = a.id
            WHERE b.session_id IS NULL AND a.username IS NULL AND a.gold_level = 0");

        $unused = $q->fetchAll(Database::FETCH_COLUMN);

        return $unused;
    }

    public function getExpiredSessions() : array
    {
        $q = $this->db->query("SELECT a.session_id FROM user_sessions a
            LEFT JOIN users b ON  b.id = a.user_id
            WHERE a.last_active < DATE_SUB(NOW(), INTERVAL 3 DAY) AND b.username IS NULL AND b.gold_level = 0");
        $expired_a = $q->fetchAll(Database::FETCH_COLUMN);

        $q = $this->db->query("SELECT a.session_id FROM user_sessions a
            LEFT JOIN users b ON  b.id = a.user_id
            WHERE a.last_active < DATE_SUB(NOW(), INTERVAL 1 MONTH) AND b.username IS NOT NULL");
        $expired_b = $q->fetchAll(Database::FETCH_COLUMN);

        return array_merge($expired_a, $expired_b);
    }
}
