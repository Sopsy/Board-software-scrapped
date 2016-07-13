<?php
namespace YBoard\Model;

use YBoard\Library\Database;
use YBoard\Model;

class User extends Model
{
    const PASSWORD_HASH_COST = 12;
    const PASSWORD_HASH_TYPE = PASSWORD_BCRYPT;

    public $id = null;
    public $session;
    public $accountCreated;
    public $username;
    public $class = 0;
    public $goldLevel = 0;
    public $lastActive;
    public $lastIp;
    public $isRegistered = false;
    public $loggedIn = false;
    public $isMod = false;
    public $isAdmin = false;
    public $requireCaptcha = true;

    public $preferences;
    public $statistics;
    public $threadHide;
    public $threadFollow;

    public function __construct(Database $db)
    {
        parent::__construct($db);
    }

    public function loadById(int $userId) : bool
    {
        $q = $this->db->prepare("SELECT id, username, class, gold_level, account_created, last_active, last_ip
            FROM users WHERE id = :user_id LIMIT 1");
        $q->bindValue('user_id', $userId);
        $q->execute();

        if ($q->rowCount() == 0) {
            return false;
        }

        $this->setVariables($q->fetch());

        return true;
    }

    public function loadByUsername($username)
    {
        $q = $this->db->prepare("SELECT id, username, class, gold_level, account_created, last_active, last_ip
            FROM users WHERE username = :username LIMIT 1");
        $q->bindValue('username', $username);
        $q->execute();

        if ($q->rowCount() == 0) {
            return false;
        }

        $this->setVariables($q->fetch());

        return true;
    }

    public function createTemporary()
    {
        $this->lastActive = $this->accountCreated = date('Y-m-d H:i:s');
        $this->loadSubclasses(true);
    }

    public function create() : bool
    {
        $q = $this->db->prepare("INSERT INTO users (last_ip) VALUES (:last_ip)");
        $q->bindValue('last_ip', inet_pton($_SERVER['REMOTE_ADDR']));
        $q->execute();

        $this->id = $this->db->lastInsertId();
        $this->lastActive = $this->accountCreated = date('Y-m-d H:i:s');
        $this->loadSubclasses(true);

        return true;
    }

    protected function loadSubclasses(bool $skipDbLoad = false) : bool
    {
        $this->preferences = new UserPreferences($this->db, $this->id, $skipDbLoad);
        $this->statistics = new UserStatistics($this->db, $this->id, $skipDbLoad);
        $this->threadHide = new UserThreadHide($this->db, $this->id, $skipDbLoad);
        $this->threadFollow = new UserThreadFollow($this->db, $this->id, $skipDbLoad);

        return true;
    }

    public function delete(int $userId = null) : bool
    {
        if (!$userId) {
            $userId = $this->id;
        }

        // Relations will handle the deletion of rest of the data, so we don't have to care.
        // Thank you relations!
        $q = $this->db->prepare("DELETE FROM users WHERE id = :user_id LIMIT 1");
        $q->bindValue('user_id', $userId);
        $q->execute();

        return true;
    }

    public function getByLogin($username, $password)
    {
        $q = $this->db->prepare("SELECT id, username, password, class FROM users WHERE username = :username LIMIT 1");
        $q->bindValue('username', $username);
        $q->execute();

        if ($q->rowCount() == 0) {
            return false;
        }

        $user = $q->fetch();

        if (password_verify($password, $user->password)) {
            $newUser = new self($this->db);
            $newUser->id = $user->id;
            $newUser->class = $user->class;

            return $newUser;
        }

        return false;
    }

    public function validatePassword($password) : bool
    {
        $q = $this->db->prepare("SELECT id, username, password, class FROM users WHERE username = :username LIMIT 1");
        $q->bindValue('username', $this->username);
        $q->execute();

        if ($q->rowCount() == 0) {
            return false;
        }

        $user = $q->fetch();

        if (password_verify($password, $user->password)) {
            return true;
        }

        return false;
    }

    public function setPassword($newPassword) : bool
    {
        // Do note that this function does not verify old password!
        $newPassword = password_hash($newPassword, static::PASSWORD_HASH_TYPE, ['cost' => static::PASSWORD_HASH_COST]);

        $q = $this->db->prepare("UPDATE users SET password = :new_password WHERE id = :user_id LIMIT 1");
        $q->bindValue('new_password', $newPassword);
        $q->bindValue('user_id', $this->id);
        $q->execute();

        return true;
    }

    public function setUsername($newUsername) : bool
    {
        $q = $this->db->prepare("UPDATE users SET username = :new_username WHERE id = :user_id LIMIT 1");
        $q->bindValue('new_username', $newUsername);
        $q->bindValue('user_id', $this->id);
        $q->execute();

        return true;
    }

    public function usernameIsFree($username) : bool
    {
        $q = $this->db->prepare("SELECT id FROM users WHERE username LIKE :username LIMIT 1");
        $q->bindValue('username', $username);
        $q->execute();

        if ($q->rowCount() == 0) {
            return true;
        }

        return false;
    }

    public function isBanned() : bool
    {
        $q = $this->db->prepare("SELECT id FROM bans WHERE ip = :ip OR user_id = :user_id AND is_expired = 0 LIMIT 1");
        $q->bindValue('ip', inet_pton($_SERVER['REMOTE_ADDR']));
        $q->bindValue('user_id', $this->id);
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

    public function updateLastActive() : bool
    {
        $q = $this->db->prepare("UPDATE users SET last_active = NOW(), last_ip = :last_ip WHERE id = :user_id LIMIT 1");
        $q->bindValue('user_id', (int)$this->id);
        $q->bindValue('last_ip', inet_pton($_SERVER['REMOTE_ADDR']));
        $q->execute();

        return true;
    }

    protected function setVariables($data)
    {
        $this->id = $data->id;
        $this->accountCreated = $data->account_created;
        $this->username = $data->username;
        $this->class = $data->class;
        $this->goldLevel = $data->gold_level;
        $this->lastActive = $data->last_active;
        $this->lastIp = inet_ntop($data->last_ip);
        $this->isRegistered = $this->loggedIn = !empty($data->username); // Doubled just for clarity

        $this->loadSubclasses();

        // TODO: Maybe change to sentPosts > n instead
        $this->requireCaptcha = !$this->isRegistered;

        if ($this->class == 1) {
            $this->isMod = true;
            $this->isAdmin = true;
        } elseif ($this->class == 2) {
            $this->isMod = true;
        }
    }
}
