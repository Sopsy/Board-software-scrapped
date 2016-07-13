<?php
namespace YBoard\Model;

use YBoard\Abstracts\UserSubModel;
use YBoard\Library\Database;

class UserThreadFollow extends UserSubModel
{
    public $threads = [];

    public function add(int $threadId) : bool
    {
        $q = $this->db->prepare("INSERT IGNORE INTO user_thread_follow (user_id, thread_id) VALUES (:user_id, :thread_id)");
        $q->bindValue('user_id', $this->userId);
        $q->bindValue('thread_id', $threadId);
        $q->execute();

        return true;
    }

    public function remove(int $threadId) : bool
    {
        $q = $this->db->prepare("DELETE FROM user_thread_follow WHERE user_id = :user_id AND thread_id = :thread_id");
        $q->bindValue('user_id', $this->userId);
        $q->bindValue('thread_id', $threadId);
        $q->execute();

        return true;
    }

    public function setLastSeenReply(int $lastSeenId, int $threadId) : bool
    {
        $q = $this->db->prepare("UPDATE user_thread_follow SET last_seen_reply = :last_seen_id
            WHERE thread_id = :thread_id AND user_id = :user_id LIMIT 1");
        $q->bindValue('thread_id', $lastSeenId);
        $q->bindValue('thread_id', $threadId);
        $q->bindValue('user_id', $this->userId);
        $q->execute();

        return true;
    }

    public function incrementUnreadCount(int $threadId) : bool
    {
        $q = $this->db->prepare("UPDATE user_thread_follow SET unread_count = unread_count+1 WHERE thread_id = :thread_id");
        $q->bindValue('thread_id', $threadId);
        $q->execute();

        return true;
    }

    protected function load() : bool
    {
        $q = $this->db->prepare("SELECT thread_id FROM user_thread_follow WHERE user_id = :user_id");
        $q->bindValue('user_id', $this->userId);
        $q->execute();

        $this->threads = $q->fetchAll(Database::FETCH_COLUMN);

        return true;
    }
}
