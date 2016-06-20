<?php
namespace YBoard\Model;

use YFW\Library\Database;
use YBoard\Model;

class UserSession extends Model
{
    public $id;
    public $userId;
    public $verifyKey;
    public $csrfToken;
    public $ip;
    public $loginTime;
    public $lastActive;

    public function __construct(Database $db, \stdClass $data = null)
    {
        parent::__construct($db);

        if (!$data) {
            return;
        }

        foreach ($data as $key => $val) {
            switch ($key) {
                case 'id':
                    $this->id = $val;
                    break;
                case 'user_id':
                    $this->userId = (int)$val;
                    break;
                case 'csrf_token':
                    $this->csrfToken = bin2hex($val);
                    break;
                case 'ip':
                    $this->ip = inet_ntop($val);
                    break;
                case 'login_time':
                    $this->loginTime = $val;
                    break;
                case 'last_active':
                    $this->lastActive = $val;
                    break;
            }
        }
    }

    public function updateLastActive(): bool
    {
        if ($this->id === null) {
            return false;
        }

        $q = $this->db->prepare("UPDATE user_session SET last_active = NOW(), ip = :ip WHERE id = :id LIMIT 1");
        $q->bindValue(':id', $this->id);
        $q->bindValue(':ip', inet_pton($_SERVER['REMOTE_ADDR']));
        $q->execute();

        return true;
    }

    public function destroy(): bool
    {
        $q = $this->db->prepare("DELETE FROM user_session WHERE id = :id LIMIT 1");
        $q->bindValue(':id', $this->id);
        $q->execute();

        return true;
    }

    public static function get(
        Database $db,
        int $userId,
        string $sessionId,
        string $verifyKey,
        bool $verifyValidity = true
    ): ?self {
        $q = $db->prepare("SELECT id, user_id, verify_key, csrf_token, ip, login_time, last_active
            FROM user_session WHERE id = :id AND user_id = :user_id LIMIT 1");
        $q->bindValue(':id', $sessionId);
        $q->bindValue(':user_id', $userId, Database::PARAM_INT);
        $q->execute();

        if ($q->rowCount() == 0) {
            return null;
        }

        $row = $q->fetch();

        if ($verifyValidity && !hash_equals($row->verify_key, $verifyKey)) {
            return null;
        }

        return new self($db, $row);
    }

    public static function getAll(Database $db, int $userId): array
    {
        $q = $db->prepare("SELECT id, user_id, csrf_token, ip, login_time, last_active
            FROM user_session WHERE user_id = :user_id");
        $q->bindValue(':user_id', $userId, Database::PARAM_INT);
        $q->execute();

        $sessions = [];
        while ($row = $q->fetch()) {
            $sessions[] = new self($db, $row);
        }

        return $sessions;
    }

    public static function create(Database $db, int $userId): self
    {
        $sessionId = random_bytes(32);
        $csrfToken = random_bytes(32);
        $verifyKey = random_bytes(32);

        $q = $db->prepare("INSERT INTO user_session (id, user_id, verify_key, csrf_token, ip)
            VALUES (:id, :user_id, :verify_key, :csrf_token, :ip)");
        $q->bindValue(':id', $sessionId, Database::PARAM_INT);
        $q->bindValue(':user_id', $userId, Database::PARAM_INT);
        $q->bindValue(':verify_key', $verifyKey);
        $q->bindValue(':csrf_token', $csrfToken);
        $q->bindValue(':ip', inet_pton($_SERVER['REMOTE_ADDR']));
        $q->execute();

        $session = new self($db);
        $session->id = $sessionId;
        $session->userId = $userId;
        $session->verifyKey = $verifyKey;
        $session->csrfToken = bin2hex($csrfToken);
        $session->ip = $_SERVER['REMOTE_ADDR'];
        $session->loginTime = $session->lastActive = date('Y-m-d H:i:s');

        return $session;
    }

    static public function getExpiredIds(Database $db): array
    {
        $q = $db->query("SELECT a.id FROM user_session a
            LEFT JOIN user b ON  b.id = a.user_id
            WHERE a.last_active < DATE_SUB(NOW(), INTERVAL 3 DAY) AND b.username IS NULL AND b.gold_level = 0");
        $expired_a = $q->fetchAll(Database::FETCH_COLUMN);

        $q = $db->query("SELECT a.id FROM user_session a
            LEFT JOIN user b ON  b.id = a.user_id
            WHERE a.last_active < DATE_SUB(NOW(), INTERVAL 1 MONTH) AND b.username IS NOT NULL");
        $expired_b = $q->fetchAll(Database::FETCH_COLUMN);

        return array_merge($expired_a, $expired_b);
    }

    static public function destroyMany(Database $db, array $sessionIds): bool
    {
        $in = $db->buildIn($sessionIds);
        $q = $db->prepare("DELETE FROM user_session WHERE id IN (" . $in . ")");
        $q->execute($sessionIds);

        return $q->rowCount() != 0;
    }
}
