<?php
namespace YBoard\Model;

use YBoard\Library\Database;
use YBoard\Model;

class UserSessions extends Model
{
    public function get(int $userId, $sessionId)
    {
        $q = $this->db->prepare("SELECT id, user_id, csrf_token, ip, login_time, last_active
            FROM user_sessions WHERE id = :id AND user_id = :user_id LIMIT 1");
        $q->bindValue('id', $sessionId);
        $q->bindValue('user_id', $userId);
        $q->execute();

        if ($q->rowCount() == 0) {
            return false;
        }

        return new UserSession($this->db, $q->fetch());
    }

    public function getAll(int $userId) : array
    {
        $q = $this->db->prepare("SELECT id, user_id, csrf_token, ip, login_time, last_active
            FROM user_sessions WHERE user_id = :user_id");
        $q->bindValue('user_id', $userId);
        $q->execute();

        $sessions = [];
        while ($row = $q->fetch()) {
            $sessions[] = new UserSession($this->db, $row);
        }

        return $sessions;
    }

    public function create(int $userId) : UserSession
    {
        $sessionId = random_bytes(32);
        $csrfToken = random_bytes(32);

        $q = $this->db->prepare("INSERT INTO user_sessions (user_id, id, csrf_token, ip)
            VALUES (:user_id, :id, :csrf_token, :ip)");
        $q->bindValue('user_id', $userId);
        $q->bindValue('id', $sessionId);
        $q->bindValue('csrf_token', $csrfToken);
        $q->bindValue('ip', inet_pton($_SERVER['REMOTE_ADDR']));
        $q->execute();

        $session = new UserSession($this->db);
        $session->id = $sessionId;
        $session->userId = $userId;
        $session->csrfToken = bin2hex($csrfToken);
        $session->ip = $_SERVER['REMOTE_ADDR'];
        $session->loginTime = $session->lastActive = date('Y-m-d H:i:s');

        return $session;
    }

    public function getExpiredIds() : array
    {
        $q = $this->db->query("SELECT a.id FROM user_sessions a
            LEFT JOIN users b ON  b.id = a.user_id
            WHERE a.last_active < DATE_SUB(NOW(), INTERVAL 3 DAY) AND b.username IS NULL AND b.gold_level = 0");
        $expired_a = $q->fetchAll(Database::FETCH_COLUMN);

        $q = $this->db->query("SELECT a.id FROM user_sessions a
            LEFT JOIN users b ON  b.id = a.user_id
            WHERE a.last_active < DATE_SUB(NOW(), INTERVAL 1 MONTH) AND b.username IS NOT NULL");
        $expired_b = $q->fetchAll(Database::FETCH_COLUMN);

        return array_merge($expired_a, $expired_b);
    }

    public function destroyMany(array $sessionIds) : bool
    {
        $in = $this->db->buildIn($sessionIds);
        $q = $this->db->prepare("DELETE FROM user_sessions WHERE id IN (" . $in . ")");
        $q->execute($sessionIds);

        return $q->rowCount() != 0;
    }
}
