<?php
namespace YBoard\Model;

use YBoard\Model;
use YFW\Library\Database;

class UserThreadFollow extends Model
{
    public $unreadCount = 0;
    public $threadId;
    public $lastSeenReply = null;

    protected $userId;

    public function __construct(Database $db, int $userId, ?\stdClass $data = null)
    {
        parent::__construct($db);
        $this->userId = $userId;

        if ($data === null) {
            return;
        }

        foreach ($data as $key => $val) {
            switch ($key) {
                case 'unread_count':
                    $this->unreadCount = (int)$val;
                    break;
                case 'thread_id':
                    $this->threadId = (int)$val;
                    break;
                case 'last_seen_reply':
                    $this->lastSeenReply = $val === null ? null : (int)$val;
                    break;
            }
        }
    }

    public function setLastSeenReply(int $lastSeenId): bool
    {
        $q = $this->db->prepare("UPDATE user_thread_follow SET last_seen_reply = :last_seen_id
            WHERE user_id = :user_id AND thread_id = :thread_id LIMIT 1");
        $q->bindValue(':last_seen_id', $lastSeenId, Database::PARAM_INT);
        $q->bindValue(':user_id', $this->userId, Database::PARAM_INT);
        $q->bindValue(':thread_id', $this->threadId, Database::PARAM_INT);
        $q->execute();

        return true;
    }

    public function markRead(): bool
    {
        $q = $this->db->prepare("UPDATE user_thread_follow SET unread_count = 0
            WHERE user_id = :user_id AND thread_id = :thread_id LIMIT 1");
        $q->bindValue(':user_id', $this->userId, Database::PARAM_INT);
        $q->bindValue(':thread_id', $this->threadId, Database::PARAM_INT);
        $q->execute();

        $this->unreadCount = 0;

        return true;
    }

    public function delete(): bool
    {
        $q = $this->db->prepare("DELETE FROM user_thread_follow
            WHERE user_id = :user_id AND thread_id = :thread_id LIMIT 1");
        $q->bindValue(':user_id', $this->userId, Database::PARAM_INT);
        $q->bindValue(':thread_id', $this->threadId, Database::PARAM_INT);
        $q->execute();

        return true;
    }

    public static function create(Database $db, int $userId, int $threadId): self
    {
        $q = $db->prepare("INSERT IGNORE INTO user_thread_follow (user_id, thread_id) VALUES (:user_id, :thread_id)");
        $q->bindValue(':user_id', $userId, Database::PARAM_INT);
        $q->bindValue(':thread_id', $threadId, Database::PARAM_INT);
        $q->execute();

        $followed = new self($db, $userId);
        $followed->threadId = $threadId;

        return $followed;
    }

    public static function getEmpty(): array
    {
        return [
            'unreadThreads' => 0,
            'unreadPosts' => 0,
            'list' => [],
        ];
    }

    public static function markAllReadByUser(Database $db, int $userId): bool
    {
        $q = $db->prepare("UPDATE user_thread_follow SET unread_count = 0
            WHERE user_id = :user_id");
        $q->bindValue(':user_id', $userId, Database::PARAM_INT);
        $q->execute();

        return true;
    }

    public static function getFollowers(Database $db, int $threadId): array
    {
        $q = $db->prepare("SELECT user_id FROM user_thread_follow WHERE thread_id = :thread_id");
        $q->bindValue(':thread_id', $threadId, Database::PARAM_INT);
        $q->execute();

        return $q->fetchAll(Database::FETCH_COLUMN);
    }

    public static function incrementUnreadCount(Database $db, int $threadId, int $userNot = 0): bool
    {
        $q = $db->prepare("UPDATE user_thread_follow SET unread_count = unread_count+1
            WHERE thread_id = :thread_id AND user_id != :user_id");
        $q->bindValue(':thread_id', $threadId, Database::PARAM_INT);
        $q->bindValue(':user_id', $userNot, Database::PARAM_INT);
        $q->execute();

        return true;
    }

    public static function getByUser(Database $db, int $userId): array
    {
        $q = $db->prepare("SELECT thread_id, last_seen_reply, unread_count
            FROM user_thread_follow WHERE user_id = :user_id ORDER BY unread_count DESC");
        $q->bindValue(':user_id', $userId, Database::PARAM_INT);
        $q->execute();

        $unreadThreadCount = 0;
        $unreadPostCount = 0;
        $threads = [];

        while ($data = $q->fetch()) {
            $threads[$data->thread_id] = new self($db, $userId, $data);
            if ($data->unread_count !== 0) {
                ++$unreadThreadCount;
                $unreadPostCount += $data->unread_count;
            }
        }

        return [
            'unreadThreads' => $unreadThreadCount,
            'unreadPosts' => $unreadPostCount,
            'list' => $threads,
        ];
    }
}
