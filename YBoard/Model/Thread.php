<?php
namespace YBoard\Model;

use YBoard\Library\Database;
use YBoard\Library\Text;
use YBoard\Traits\PostsQuery;

class Thread extends Post
{
    use PostsQuery;

    public $boardUrl;
    public $subject;
    public $locked = false;
    public $sticky = false;
    public $replies = false;
    public $replyCount = 0;
    public $distinctReplyCount = 0;
    public $readCount = 0;

    public function __construct(Database $db, $data, $maxReplies)
    {
        parent::__construct($db, $data);

        foreach ($data as $key => $val) {
            switch ($key) {
                case 'board_url':
                    $this->boardUrl = $val;
                    break;
                case 'locked':
                    $this->locked = (bool)$val;
                    break;
                case 'sticky':
                    $this->sticky = (bool)$val;
                    break;
                case 'subject':
                    $this->subject = $val;
                    break;
                case 'read_count':
                    $this->readCount = (int)$val;
                    break;
                case 'reply_count':
                    $this->replyCount = (int)$val;
                    break;
                case 'distinct_reply_count':
                    $this->distinctReplyCount = (int)$val;
                    break;
            }
        }

        if (empty($this->subject) && $this->subject != '0' && !empty($this->message)) {
            $this->subject = $this->createSubject($this->message);
        }

        if (!$maxReplies) {
            $this->replies = false;
        } elseif ($maxReplies >= 10000) {
            $this->replies = $this->getReplies();
        } else {
            $this->replies = $this->getReplies($maxReplies, true);
        }
    }

    public function bump() : bool
    {
        $q = $this->db->prepare("UPDATE posts SET bump_time = NOW() WHERE id = :thread_id LIMIT 1");
        $q->bindValue('thread_id', $this->id);
        $q->execute();

        return true;
    }

    public function undoLastBump() : bool
    {
        $q = $this->db->prepare("UPDATE posts a LEFT JOIN posts b ON a.id = b.thread_id
            SET a.bump_time = IFNULL(b.time, a.time) WHERE a.id = :thread_id");
        $q->bindValue('thread_id', (int)$this->id);
        $q->execute();

        return true;
    }

    public function addReply(
        int $userId,
        string $message,
        $username,
        string $countryCode
    ) : Reply
    {
        $q = $this->db->prepare("INSERT INTO posts
            (user_id, thread_id, ip, country_code, username, message)
            VALUES (:user_id, :thread_id, :ip, :country_code, :username, :message)
        ");
        $q->bindValue('user_id', $userId);
        $q->bindValue('thread_id', $this->id);
        $q->bindValue('ip', inet_pton($_SERVER['REMOTE_ADDR']));
        $q->bindValue('country_code', $countryCode);
        $q->bindValue('username', $username);
        $q->bindValue('message', $message);
        $q->execute();

        $reply = new Reply($this->db);
        $reply->id = $this->db->lastInsertId();

        return $reply;
    }

    public function getReplies(int $count = null, bool $newest = false, int $fromId = null) : array
    {
        $from = '';
        if ($newest) {
            $order = 'DESC';
            if ($fromId) {
                $from = ' AND a.id > :from';
            }
        } else {
            $order = 'ASC';
            if ($fromId) {
                $from = ' AND a.id < :from';
            }
        }

        if ($count) {
            $limit = ' LIMIT ' . (int)$count;
        } else {
            $limit = '';
        }

        $q = $this->db->prepare($this->getPostsQuery('WHERE a.thread_id = :thread_id' . $from . ' ORDER BY a.id ' . $order . $limit));
        $q->bindValue('thread_id', $this->id);
        if ($from) {
            $q->bindValue('from', $fromId);
        }
        $q->execute();

        $replies = [];
        while ($row = $q->fetch()) {
            $row->thread_id = $this->id;
            $replies[] = new Reply($this->db, $row);
        }

        if ($newest) {
            $replies = array_reverse($replies);
        }

        return $replies;
    }

    public function updateStats(string $key, int $val = 1) : bool
    {
        switch ($key) {
            case "replyCount":
                $column = 'reply_count';
                break;
            case "readCount":
                $column = 'read_count';
                break;
            case "followCount":
                $column = 'follow_count';
                break;
            case "hideCount":
                $column = 'hide_count';
                break;
            default:
                return false;
        }

        $q = $this->db->prepare("INSERT INTO thread_statistics (thread_id, " . $column . ") VALUES (:thread_id, :val)
            ON DUPLICATE KEY UPDATE " . $column . " =  " . $column . "+:val_2");

        $q->bindValue('thread_id', $this->id);
        $q->bindValue('val', $val);
        $q->bindValue('val_2', $val);
        $q->execute();

        return true;
    }

    protected function createSubject(string $message) : string
    {
        $subject = Text::stripFormatting($message);
        $subject = Text::truncate($subject, 40);
        $subject = trim($subject);

        return $subject;
    }
}
