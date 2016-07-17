<?php
namespace YBoard\Model;

use YBoard\Library\Database;
use YBoard\Model;

class Users extends Model
{
    public function __construct(Database $db)
    {
        parent::__construct($db);
    }

    public function getById(int $userId)
    {
        $q = $this->db->prepare("SELECT id, username, password, class, gold_level, account_created, last_active, last_ip
            FROM users WHERE id = :user_id LIMIT 1");
        $q->bindValue('user_id', $userId);
        $q->execute();

        if ($q->rowCount() == 0) {
            return false;
        }

        return new User($this->db, $q->fetch());
    }

    public function getByUsername($username)
    {
        $q = $this->db->prepare("SELECT id, username, password, class, gold_level, account_created, last_active, last_ip
            FROM users WHERE username = :username LIMIT 1");
        $q->bindValue('username', $username);
        $q->execute();

        if ($q->rowCount() == 0) {
            return false;
        }

        return new User($this->db, $q->fetch());
    }

    public function getByLogin($username, $password)
    {
        $q = $this->db->prepare("SELECT id, username, password, class FROM users WHERE username = :username LIMIT 1");
        $q->bindValue('username', $username);
        $q->execute();

        if ($q->rowCount() == 0) {
            return false;
        }

        $user = new User($this->db, $q->fetch());
        if ($user->validatePassword($password)) {
            return $user;
        }

        return false;
    }

    public function createTemporary() : User
    {
        $user = new User($this->db, [], true);
        $user->lastActive = $user->accountCreated = date('Y-m-d H:i:s');

        return $user;
    }

    public function create() : User
    {
        $q = $this->db->prepare("INSERT INTO users (last_ip) VALUES (:last_ip)");
        $q->bindValue('last_ip', inet_pton($_SERVER['REMOTE_ADDR']));
        $q->execute();

        $user = new User($this->db, [], true);
        $user->id = $this->db->lastInsertId();
        $user->lastActive = $user->accountCreated = date('Y-m-d H:i:s');

        return $user;
    }

    public function deleteMany($userId) : bool
    {
        // Relations will handle the deletion of rest of the data, so we don't have to care.
        // Thank you relations!
        $q = $this->db->prepare("DELETE FROM users WHERE id = :user_id");
        $q->bindValue('user_id', $userId);
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

    // Get user accounts that have no active sessions and cannot be logged in to
    public function getUnusable() : array
    {
        $q = $this->db->query("SELECT a.id FROM users a
            LEFT JOIN user_sessions b ON b.user_id = a.id
            WHERE b.session_id IS NULL AND a.username IS NULL AND a.gold_level = 0");

        $unused = $q->fetchAll(Database::FETCH_COLUMN);

        return $unused;
    }
}
