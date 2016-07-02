<?php
namespace YBoard\Model;

use YBoard\Model;
use YBoard\Library\Text;

class Posts extends Model
{
    public function getThread($id, $metaOnly = false)
    {
        if (!$metaOnly) {
            $selectFields = ', username, subject, message';
        } else {
            $selectFields = '';
        }

        $q = $this->db->prepare('SELECT id, board_id, upvote_count, user_id, ip, country_code, time, locked,
            sticky' . $selectFields . ' FROM posts WHERE id = :id AND thread_id IS NULL LIMIT 1');
        $q->bindValue('id', (int)$id);
        $q->execute();

        if ($q->rowCount() == 0) {
            return false;
        }

        $post = $q->fetch();

        if (!$metaOnly && empty($post->subject)) {
            $post->subject = $this->createSubject($post->message);
        }

        // Assign values to a class to return
        // Maybe create a "Thread" -class instead of stdClass?
        $thread = new \stdClass();
        $thread->id = $post->id;
        $thread->locked = (bool)$post->locked;
        $thread->boardId = $post->board_id;
        $thread->userId = $post->user_id;
        $thread->ip = inet_ntop($post->ip);
        $thread->countryCode = $post->country_code;
        $thread->time = strtotime($post->time . ' UTC');
        $thread->locked = $post->locked;
        $thread->sticky = $post->sticky;
        $thread->points = $post->upvote_count;

        if (!$metaOnly) {
            $thread->username = $post->username;
            $thread->subject = $post->subject;
            $thread->message = $post->message;
            $thread->replies = $this->getReplies($post->id);
        }

        return $thread;
    }

    protected function getReplies(int $threadId, int $count = null, bool $newest = false) : array
    {
        if ($newest) {
            $order = 'DESC';
        } else {
            $order = 'ASC';
        }

        if ($count) {
            $limit = ' LIMIT ' . (int)$count;
        } else {
            $limit = '';
        }

        $q = $this->db->prepare('SELECT id, user_id, upvote_count, ip, country_code, username, time, message
            FROM posts WHERE thread_id = :thread_id ORDER BY id ' . $order . $limit);
        $q->bindValue('thread_id', $threadId);
        $q->execute();

        $replies = [];
        while ($reply = $q->fetch()) {
            // Maybe create a "Reply" -class instead of stdClass?
            $tmp = new \stdClass();
            $tmp->id = $reply->id;
            $tmp->userId = $reply->user_id;
            $tmp->points = $reply->upvote_count;
            $tmp->ip = inet_ntop($reply->ip);
            $tmp->countryCode = $reply->country_code;
            $tmp->username = $reply->username;
            $tmp->time = strtotime($reply->time . ' UTC');
            $tmp->message = $reply->message;
            $replies[] = $tmp;
        }

        if ($newest) {
            $replies = array_reverse($replies);
        }

        return $replies;
    }

    protected function createSubject($message)
    {
        $subject = preg_replace('/\s\s+/', ' ', str_replace(["\n", "\r"], ' ', $message));
        $subject = Text::stripBbCode($subject);
        $subject = Text::removeForbiddenUnicode($subject);
        $subject = Text::truncate($subject, 40);
        $subject = trim($subject);

        return $subject;
    }

    public function createThread($userId, $boardId, $subject, $message, $username, $ip, $countryCode)
    {
        $q = $this->db->prepare("INSERT INTO posts
            (user_id, board_id, ip, country_code, username, subject, message, bump_time, locked, sticky)
            VALUES
            (:user_id, :board_id, :ip, :country_code, :username, :subject, :message, NOW(), 0, 0)
        ");

        $q->bindValue('user_id', (int)$userId);
        $q->bindValue('board_id', (int)$boardId);
        $q->bindValue('ip', inet_pton($ip));
        $q->bindValue('country_code', $countryCode);
        $q->bindValue('username', $username);
        $q->bindValue('subject', $subject);
        $q->bindValue('message', $message);

        $q->execute();

        return $this->db->lastInsertId();
    }

    public function addReply($userId, $threadId, $message, $username, $ip, $countryCode)
    {
        $q = $this->db->prepare("INSERT INTO posts
            (user_id, thread_id, ip, country_code, username, message)
            VALUES
            (:user_id, :thread_id, :ip, :country_code, :username, :message)
        ");

        $q->bindValue('user_id', (int)$userId);
        $q->bindValue('thread_id', (int)$threadId);
        $q->bindValue('ip', inet_pton($ip));
        $q->bindValue('country_code', $countryCode);
        $q->bindValue('username', $username);
        $q->bindValue('message', $message);

        $q->execute();

        return $this->db->lastInsertId();
    }
}
