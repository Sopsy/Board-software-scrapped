<?php
namespace YBoard\Model;

use YBoard\Abstracts\UserSubModel;
use YBoard\Data\FollowedThread;
use YBoard\Library\Database;

class UserThreadFollow extends UserSubModel
{
    public $threads = [];
    public $unreadCount = 0;

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

    public function exists(int $threadId) : bool
    {
        return array_key_exists($threadId, $this->threads);
    }

    public function getFollowers(int $threadId) : array
    {
        $q = $this->db->prepare("SELECT user_id FROM user_thread_follow WHERE thread_id = :thread_id");
        $q->bindValue('thread_id', $threadId);
        $q->execute();

        return $q->fetchAll(Database::FETCH_COLUMN);
    }

    public function setLastSeenReply(int $lastSeenId, int $threadId) : bool
    {
        $q = $this->db->prepare("UPDATE user_thread_follow SET last_seen_reply = :last_seen_id
            WHERE thread_id = :thread_id AND user_id = :user_id LIMIT 1");
        $q->bindValue('last_seen_id', $lastSeenId);
        $q->bindValue('thread_id', $threadId);
        $q->bindValue('user_id', $this->userId);
        $q->execute();

        return true;
    }

    public function incrementUnreadCount(int $threadId, int $userNot = 0) : bool
    {
        $q = $this->db->prepare("UPDATE user_thread_follow SET unread_count = unread_count+1
            WHERE thread_id = :thread_id AND user_id != :user_id");
        $q->bindValue('thread_id', $threadId);
        $q->bindValue('user_id', $userNot);
        $q->execute();

        return true;
    }

    public function resetUnreadCount(int $threadId) : bool
    {
        $q = $this->db->prepare("UPDATE user_thread_follow SET unread_count = 0
            WHERE thread_id = :thread_id AND user_id = :user_id");
        $q->bindValue('thread_id', $threadId);
        $q->bindValue('user_id', $this->userId);
        $q->execute();

        return true;
    }

    protected function load() : bool
    {
        $q = $this->db->prepare("SELECT thread_id, last_seen_reply, unread_count
            FROM user_thread_follow WHERE user_id = :user_id");
        $q->bindValue('user_id', $this->userId);
        $q->execute();

        $this->threads = [];
        while ($data = $q->fetch()) {
            $thread = new FollowedThread();
            $thread->id = $data->thread_id;
            $thread->lastSeenReply = $data->last_seen_reply;
            $thread->unreadCount = $data->unread_count;

            $this->threads[$data->thread_id] = $thread;
        }

        if (!empty($this->threads)) {
            $q = $this->db->prepare("SELECT SUM(unread_count) AS unread_count FROM user_thread_follow
            WHERE user_id = :user_id LIMIT 1");
            $q->bindValue('user_id', $this->userId);
            $q->execute();

            $this->unreadCount = $q->fetch(Database::FETCH_COLUMN);
        }

        return true;
    }
}
