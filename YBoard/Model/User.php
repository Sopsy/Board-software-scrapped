<?php
namespace YBoard\Model;

use YFW\Library\Database;
use YBoard\Model;

class User extends Model
{
    const PASSWORD_HASH_COST = 12;
    const PASSWORD_HASH_TYPE = PASSWORD_BCRYPT;

    public $id = null;
    public $accountCreated;
    public $username;
    public $class = 0;
    public $goldLevel = 0;
    public $lastActive;
    public $lastIp;
    public $isRegistered = false;
    public $loggedIn = false;
    public $ban = false;
    public $isMod = false;
    public $isAdmin = false;

    public $session;
    public $preferences;
    public $statistics;
    public $threadHide;
    public $threadFollow;
    public $notifications;

    protected $password;

    public function __construct(Database $db, ?\stdClass $data = null)
    {
        parent::__construct($db);

        if ($data !== null) {
            foreach ($data as $key => $val) {
                switch ($key) {
                    case 'id':
                        $this->id = (int)$val;
                        break;
                    case 'account_created':
                        $this->accountCreated = $val;
                        break;
                    case 'username':
                        $this->username = $val;
                        break;
                    case 'password':
                        $this->password = $val;
                        break;
                    case 'class':
                        $this->class = (int)$val;
                        break;
                    case 'gold_level':
                        $this->goldLevel = (int)$val;
                        break;
                    case 'last_active':
                        $this->lastActive = $val;
                        break;
                    case 'last_ip':
                        $this->lastIp = inet_ntop($val);
                        break;
                }
            }
        }

        $this->isRegistered = $this->loggedIn = !empty($data->username); // Doubled just for clarity
        $this->ban = $this->getBan();

        if ($this->class == 1) {
            $this->isMod = true;
            $this->isAdmin = true;
        } elseif ($this->class == 2) {
            $this->isMod = true;
        }

        if ($this->goldLevel >= 1) {
            // Override settings for golds
            // TODO: add file maxsize
        }
    }
    public function delete(): bool
    {
        // Relations will handle the deletion of rest of the data, so we don't have to care.
        // Thank you relations!
        $q = $this->db->prepare("DELETE FROM user WHERE id = :id LIMIT 1");
        $q->bindValue(':id', $this->id, Database::PARAM_INT);
        $q->execute();

        return true;
    }

    public function validatePassword(string $password): bool
    {
        if (password_verify($password, $this->password)) {
            return true;
        }

        return false;
    }

    public function setPassword(string $newPassword): bool
    {
        // Do note that this function does not verify old password!
        $newPassword = password_hash($newPassword, static::PASSWORD_HASH_TYPE, ['cost' => static::PASSWORD_HASH_COST]);

        $q = $this->db->prepare("UPDATE user SET password = :new_password WHERE id = :id LIMIT 1");
        $q->bindValue(':new_password', $newPassword);
        $q->bindValue(':id', $this->id, Database::PARAM_INT);
        $q->execute();

        return true;
    }

    public function setUsername(string $newUsername): bool
    {
        $q = $this->db->prepare("UPDATE user SET username = :new_username WHERE id = :id LIMIT 1");
        $q->bindValue(':new_username', $newUsername);
        $q->bindValue(':id', $this->id);
        $q->execute();

        return true;
    }

    public function updateLastActive(): bool
    {
        $q = $this->db->prepare("UPDATE user SET last_active = NOW(), last_ip = :last_ip WHERE id = :id LIMIT 1");
        $q->bindValue(':id', (int)$this->id);
        $q->bindValue(':last_ip', inet_pton($_SERVER['REMOTE_ADDR']));
        $q->execute();

        return true;
    }

    public function getBan(): ?Ban
    {
        if ($this->id === null) {
            return null;
        }

        return Ban::get($this->db, $_SERVER['REMOTE_ADDR'], $this->id);
    }

    public function getHiddenThreads(bool $idsOnly = true): array
    {
        if (!$idsOnly) {
            return $this->threadHide['list'];
        }

        return array_keys($this->threadHide['list']);
    }

    public function getHiddenThread(int $threadId): ?UserThreadHide
    {
        if (!$this->threadIsHidden($threadId)) {
            return null;
        }

        return $this->threadHide['list'][$threadId];
    }

    public function threadIsHidden(int $threadId): bool
    {
        return array_key_exists($threadId, $this->threadHide['list']);
    }

    public function getFollowedThread(int $threadId): ?UserThreadFollow
    {
        if (!$this->threadIsFollowed($threadId)) {
            return null;
        }

        return $this->threadFollow['list'][$threadId];
    }

    public function getFollowedThreads(bool $idsOnly = true): array
    {
        if (!$idsOnly) {
            return $this->threadFollow['list'];
        }

        return array_keys($this->threadFollow['list']);
    }

    public function threadIsFollowed(int $threadId): bool
    {
        return array_key_exists($threadId, $this->threadFollow['list']);
    }

    public function markFollowedRead(int $threadId): bool
    {
        if ($this->threadFollow['unreadThreads'] === 0) {
            return true;
        }

        $followed = $this->getFollowedThread($threadId);
        if ($followed === null) {
            return false;
        }

        if ($followed->unreadCount === 0) {
            return true;
        }

        $this->threadFollow['unreadThreads'] -= 1;
        $this->threadFollow['unreadPosts'] -= $followed->unreadCount;
        $followed->markRead();

        return true;
    }

    public function getThreadLastSeenReply(int $threadId): ?int
    {
        if (!$this->threadIsFollowed($threadId)) {
            return null;
        }

        return $this->threadFollow['list'][$threadId]->lastSeenReply;
    }

    public function getThreadUnreadCount(int $threadId): ?int
    {
        if (!$this->threadIsFollowed($threadId)) {
            return null;
        }

        return $this->threadFollow['list'][$threadId]->unreadCount;
    }

    public static function getById(Database $db, int $userId): ?self
    {
        $q = $db->prepare("SELECT id, username, password, class, gold_level, account_created, last_active, last_ip
            FROM user WHERE id = :user_id LIMIT 1");
        $q->bindValue(':user_id', $userId);
        $q->execute();

        if ($q->rowCount() == 0) {
            return null;
        }

        return new self($db, $q->fetch());
    }

    public static function getByUsername(Database $db, string $username): ?self
    {
        $q = $db->prepare("SELECT id, username, password, class, gold_level, account_created, last_active, last_ip
            FROM user WHERE username = :username LIMIT 1");
        $q->bindValue(':username', $username);
        $q->execute();

        if ($q->rowCount() == 0) {
            return null;
        }

        return new self($db, $q->fetch());
    }

    public static function getByLogin(Database $db, string $username, string $password): ?self
    {
        $q = $db->prepare("SELECT id, username, password, class FROM user WHERE username = :username LIMIT 1");
        $q->bindValue(':username', $username);
        $q->execute();

        if ($q->rowCount() == 0) {
            return null;
        }

        $user = new self($db, $q->fetch());
        if ($user->validatePassword($password)) {
            return $user;
        }

        return null;
    }

    public static function createTemporary(Database $db): self
    {
        $user = new self($db, null, true);
        $user->lastActive = $user->accountCreated = date('Y-m-d H:i:s');

        return $user;
    }

    public static function create(Database $db): self
    {
        $q = $db->prepare("INSERT INTO user (last_ip) VALUES (:last_ip)");
        $q->bindValue(':last_ip', inet_pton($_SERVER['REMOTE_ADDR']));
        $q->execute();

        $user = new self($db, null, true);
        $user->id = $db->lastInsertId();
        $user->lastActive = $user->accountCreated = date('Y-m-d H:i:s');

        return $user;
    }

    public static function deleteMany(Database $db, array $userIds): bool
    {
        $in = $db->buildIn($userIds);

        // Relations will handle the deletion of rest of the data, so we don't have to care.
        // Thank you relations!
        $q = $db->prepare("DELETE FROM user WHERE id IN (" . $in . ")");
        $q->execute($userIds);

        return $q->rowCount() !== 0;
    }

    public static function usernameIsFree(Database $db, string $username): bool
    {
        $q = $db->prepare("SELECT id FROM user WHERE username LIKE :username LIMIT 1");
        $q->bindValue(':username', $username);
        $q->execute();

        if ($q->rowCount() === 0) {
            return true;
        }

        return false;
    }

    // Get user accounts that have no active sessions and cannot be logged in to
    public static function getUnusable(Database $db): array
    {
        $q = $db->query("SELECT a.id FROM user a
            LEFT JOIN user_session b ON b.user_id = a.id
            WHERE b.id IS NULL AND a.username IS NULL AND a.gold_level = 0");

        return $q->fetchAll(Database::FETCH_COLUMN);
    }

    // User cannot be logged in and has no active sessions
    public static function isUnusable(Database $db, int $userId): bool
    {
        $q = $db->prepare("SELECT a.id FROM user a
            LEFT JOIN user_session b ON b.user_id = a.id
            WHERE b.id IS NULL AND a.id = :userId AND a.username IS NULL AND a.gold_level = 0 LIMIT 1");
        $q->bindValue(':userId', $userId, Database::PARAM_INT);
        $q->execute();

        return $q->rowCount() === 1;
    }
}
