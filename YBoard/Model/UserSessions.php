<?php
namespace YBoard\Model;

use YBoard\Library\Database;
use YBoard\Model;

class UserSessions extends Model
{
    public $id;
    public $userId;
    public $csrfToken;
    public $ip;
    public $loginTime;
    public $lastActive;

    public function __construct(Database $db, int $userId = null, $sessionId = null)
    {
        parent::__construct($db);

        $this->userId = $userId;
        $this->id = $sessionId;
        
        return $this->load();
    }
    
    protected function load() : bool
    {
        if (!$this->id || !$this->userId) {
            return true;
        }
        
        $q = $this->db->prepare("SELECT session_id, user_id, csrf_token, ip, login_time, last_active
            FROM user_sessions WHERE session_id = :session_id AND user_id = :user_id LIMIT 1");
        $q->bindValue('session_id', $this->id);
        $q->bindValue('user_id', $this->userId);
        $q->execute();

        if ($q->rowCount() == 0) {
            return false;
        }

        $session = $q->fetch();
        $this->id = $session->session_id;
        $this->userId = $session->user_id;
        $this->csrfToken = bin2hex($session->csrf_token);
        $this->ip = inet_ntop($session->ip);
        $this->loginTime = $session->login_time;
        $this->lastActive = $session->last_active;

        return true;
    }

    public function getAllByUser(int $userId) : array
    {
        $q = $this->db->prepare("SELECT session_id, user_id, csrf_token, ip, login_time, last_active
            FROM user_sessions WHERE user_id = :user_id");
        $q->bindValue('user_id', $userId);
        $q->execute();

        $sessions = [];
        while ($row = $q->fetch()) {
            $tmp = new self($this->db);
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

    public function create() : bool
    {
        $sessionId = random_bytes(32);
        $csrfToken = random_bytes(32);

        $q = $this->db->prepare("INSERT INTO user_sessions (user_id, session_id, csrf_token, ip)
            VALUES (:user_id, :session_id, :csrf_token, :ip)");
        $q->bindValue('user_id', $this->userId);
        $q->bindValue('session_id', $sessionId);
        $q->bindValue('csrf_token', $csrfToken);
        $q->bindValue('ip', inet_pton($_SERVER['REMOTE_ADDR']));
        $q->execute();

        $this->id = $sessionId;
        $this->csrfToken = bin2hex($csrfToken);
        $this->ip = $_SERVER['REMOTE_ADDR'];
        $this->loginTime = date('Y-m-d H:i:s');
        $this->lastActive = date('Y-m-d H:i:s');

        return true;
    }

    public function destroy($sessionId = null) : bool
    {
        if (!$sessionId) {
            $sessionId = $this->id;
        }

        $q = $this->db->prepare("DELETE FROM user_sessions
            WHERE session_id = :session_id AND user_id = :user_id LIMIT 1");
        $q->bindValue('session_id', $sessionId);
        $q->bindValue('user_id', $this->userId);
        $q->execute();

        return true;
    }

    public function getExpired() : array
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

    public function updateLastActive() : bool
    {
        $q = $this->db->prepare("UPDATE user_sessions SET last_active = NOW(), ip = :ip
            WHERE session_id = :session_id LIMIT 1");
        $q->bindValue('session_id', $this->id);
        $q->bindValue('ip', inet_pton($_SERVER['REMOTE_ADDR']));
        $q->execute();

        return true;
    }
}
