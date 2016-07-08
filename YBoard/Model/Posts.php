<?php
namespace YBoard\Model;

use YBoard\Data\File;
use YBoard\Data\Post;
use YBoard\Data\Reply;
use YBoard\Data\Thread;
use YBoard\Data\ThreadStatistics;
use YBoard\Library\BbCode;
use YBoard\Library\Text;
use YBoard\Model;

class Posts extends Model
{
    public function getThreadMeta(int $id)
    {
        $q = $this->db->prepare("SELECT id, board_id, user_id, ip, country_code, time, locked, sticky
            FROM posts WHERE id = :id AND thread_id IS NULL LIMIT 1");
        $q->bindValue('id', (int)$id);
        $q->execute();

        if ($q->rowCount() == 0) {
            return false;
        }

        $post = $q->fetch();

        // Assign values to a class to return
        $thread = new Thread();
        $thread->id = $post->id;
        $thread->locked = (bool)$post->locked;
        $thread->boardId = $post->board_id;
        $thread->userId = $post->user_id;
        $thread->ip = inet_ntop($post->ip);
        $thread->countryCode = $post->country_code;
        $thread->time = date('c', strtotime($post->time));
        $thread->sticky = $post->sticky;

        return $thread;
    }

    public function getThread(int $id)
    {
        $q = $this->db->prepare($this->getPostsQuery("WHERE a.id = :id AND a.thread_id IS NULL LIMIT 1"));
        $q->bindValue('id', (int)$id);
        $q->execute();

        if ($q->rowCount() == 0) {
            return false;
        }

        $post = $q->fetch();

        if (empty($post->subject) && $post->subject != '0') {
            $post->subject = $this->createSubject($post->message);
        }

        // Assign values to a class to return
        $thread = new Thread();
        $thread->id = $post->id;
        $thread->locked = (bool)$post->locked;
        $thread->boardId = $post->board_id;
        $thread->userId = $post->user_id;
        $thread->ip = inet_ntop($post->ip);
        $thread->countryCode = $post->country_code;
        $thread->time = date('c', strtotime($post->time));
        $thread->sticky = $post->sticky;
        $thread->username = $this->setUsername($post->username);
        $thread->subject = $post->subject;
        $thread->message = $post->message;
        $thread->messageFormatted = $this->formatMessage($post->message);
        $thread->replies = $this->getReplies($post->id);

        $thread->statistics = new ThreadStatistics();
        $thread->statistics->readCount = $post->read_count;
        $thread->statistics->replyCount = $post->reply_count;
        $thread->statistics->distinctReplyCount = $post->distinct_reply_count;

        if (!empty($post->file_id)) {
            $thread->file = $this->createFileClass($post);
        }

        return $thread;
    }

    public function getBoardThreads(int $boardId, int $page, int $count, int $replyCount) : array
    {
        $limitStart = ($page - 1) * $count;

        $q = $this->db->prepare($this->getPostsQuery("WHERE a.board_id = :board_id AND a.thread_id IS NULL
            ORDER BY sticky DESC, bump_time DESC LIMIT " . (int)$limitStart . ', ' . (int)$count));
        $q->bindValue('board_id', $boardId);
        $q->execute();

        if ($q->rowCount() == 0) {
            return [];
        }

        $threads = [];

        while ($row = $q->fetch()) {
            if (empty($row->subject) && $row->subject != '0') {
                $row->subject = $this->createSubject($row->message);
            }

            // Assign values to a class to return
            $thread = new Thread();
            $thread->id = $row->id;
            $thread->locked = (bool)$row->locked;
            $thread->boardId = $row->board_id;
            $thread->userId = $row->user_id;
            $thread->ip = inet_ntop($row->ip);
            $thread->countryCode = $row->country_code;
            $thread->time = date('c', strtotime($row->time));
            $thread->locked = $row->locked;
            $thread->sticky = $row->sticky;
            $thread->username = $this->setUsername($row->username);
            $thread->subject = $row->subject;
            $thread->message = $row->message;
            $thread->messageFormatted = $this->formatMessage($row->message);
            $thread->replies = $this->getReplies($row->id, $replyCount, true);

            $thread->statistics = new ThreadStatistics();
            $thread->statistics->readCount = $row->read_count;
            $thread->statistics->replyCount = $row->reply_count;
            $thread->statistics->distinctReplyCount = $row->distinct_reply_count;

            if (!empty($row->file_id)) {
                $thread->file = $this->createFileClass($row);
            }

            $threads[] = $thread;
        }

        return $threads;
    }

    public function getReplies(int $threadId, int $count = null, bool $newest = false, int $fromId = null) : array
    {
        $from = '';
        if ($newest) {
            $order = 'DESC';
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
        $q->bindValue('thread_id', $threadId);
        if ($from) {
            $q->bindValue('from', $fromId);
        }
        $q->execute();

        $replies = [];
        while ($reply = $q->fetch()) {
            $tmp = new Reply();
            $tmp->id = $reply->id;
            $tmp->threadId = $threadId;
            $tmp->userId = $reply->user_id;
            $tmp->ip = inet_ntop($reply->ip);
            $tmp->countryCode = $reply->country_code;
            $tmp->username = $this->setUsername($reply->username);
            $tmp->time = date('c', strtotime($reply->time));
            $tmp->message = $reply->message;
            $tmp->messageFormatted = $this->formatMessage($reply->message);

            if (!empty($reply->file_id)) {
                $tmp->file = $this->createFileClass($reply);
            }
            $replies[] = $tmp;
        }

        if ($newest) {
            $replies = array_reverse($replies);
        }

        return $replies;
    }

    protected function createSubject(string $message) : string
    {
        $subject = preg_replace('/\s\s+/', ' ', str_replace(["\n", "\r"], ' ', $message));
        $subject = BbCode::strip($subject);
        $subject = Text::removeForbiddenUnicode($subject);
        $subject = Text::truncate($subject, 40);
        $subject = trim($subject);

        return $subject;
    }

    protected function setUsername($username) : string
    {
        if (empty($username)) {
            return _('Anonymous');
        }

        return $username;
    }

    protected function formatMessage(string $message) : string
    {
        $message = htmlspecialchars($message);
        $message = nl2br($message);
        $message = Text::clickableLinks($message);

        if (strpos($message, '&gt;') === false && strpos($message, '&lt;') === false) {
            return $message;
        }

        $search = [
            '/(^|[\n\]])(&gt;)(?!&gt;[0-9]+)([^\n]+)/is',
            '/(^|[\n\]])(&lt;)([^\n]+)/is',
            '/(&gt;&gt;)([0-9]+)/is',
        ];
        $replace = [
            '$1<span class="quote">$2$3</span>',
            '$1<span class="quote blue">$2$3</span>',
            '<a href="/scripts/posts/redirect/$2" data-id="$2" class="reflink">$1$2</a>',
        ];

        return preg_replace($search, $replace, $message);
    }

    public function createThread(
        int $userId,
        int $boardId,
        string $subject,
        string $message,
        $username,
        string $ip,
        string $countryCode
    ) : int
    {
        $q = $this->db->prepare("INSERT INTO posts
            (user_id, board_id, ip, country_code, username, subject, message, bump_time, locked, sticky)
            VALUES (:user_id, :board_id, :ip, :country_code, :username, :subject, :message, NOW(), 0, 0)
        ");

        $q->bindValue('user_id', $userId);
        $q->bindValue('board_id', $boardId);
        $q->bindValue('ip', inet_pton($ip));
        $q->bindValue('country_code', $countryCode);
        $q->bindValue('username', $username);
        $q->bindValue('subject', $subject);
        $q->bindValue('message', $message);

        $q->execute();

        return $this->db->lastInsertId();
    }

    public function addReply(
        int $userId,
        int $threadId,
        string $message,
        $username,
        string $ip,
        string $countryCode
    ) : int
    {
        $q = $this->db->prepare("INSERT INTO posts
            (user_id, thread_id, ip, country_code, username, message)
            VALUES (:user_id, :thread_id, :ip, :country_code, :username, :message)
        ");

        $q->bindValue('user_id', $userId);
        $q->bindValue('thread_id', $threadId);
        $q->bindValue('ip', inet_pton($ip));
        $q->bindValue('country_code', $countryCode);
        $q->bindValue('username', $username);
        $q->bindValue('message', $message);

        $q->execute();

        return $this->db->lastInsertId();
    }

    public function bumpThread(int $threadId) : bool
    {
        $q = $this->db->prepare("UPDATE posts SET bump_time = NOW() WHERE id = :thread_id LIMIT 1");
        $q->bindValue('thread_id', $threadId);
        $q->execute();

        return true;
    }

    public function addFile(int $postId, int $fileId, string $fileName) : bool
    {
        $q = $this->db->prepare("INSERT INTO posts_files (post_id, file_id, file_name) VALUES (:post_id, :file_id, :file_name)");
        $q->bindValue('post_id', $postId);
        $q->bindValue('file_id', $fileId);
        $q->bindValue('file_name', $fileName);
        $q->execute();

        return true;
    }

    public function getMeta(int $postId)
    {
        $q = $this->db->prepare("SELECT id, board_id, thread_id, user_id, ip, country_code, time, username
            FROM posts WHERE id = :post_id LIMIT 1");
        $q->bindValue('post_id', $postId);
        $q->execute();

        if ($q->rowCount() == 0) {
            return false;
        }

        $row = $q->fetch();
        $post = new Post();
        $post->id = $row->id;
        $post->boardId = $row->board_id;
        $post->threadId = $row->thread_id;
        $post->userId = $row->user_id;
        $post->ip = inet_ntop($row->ip);
        $post->countryCode = $row->country_code;
        $post->time = $row->time;
        $post->username = $row->username;

        return $post;
    }

    public function get(int $postId)
    {
        $q = $this->db->prepare($this->getPostsQuery('WHERE a.id = :post_id LIMIT 1'));
        $q->bindValue('post_id', $postId);
        $q->execute();

        if ($q->rowCount() == 0) {
            return false;
        }

        $reply = $q->fetch();

        $post = new Reply();
        $post->id = $reply->id;
        $post->boardId = $reply->board_id;
        $post->threadId = $reply->thread_id;
        $post->userId = $reply->user_id;
        $post->ip = inet_ntop($reply->ip);
        $post->countryCode = $reply->country_code;
        $post->username = $this->setUsername($reply->username);
        $post->time = date('c', strtotime($reply->time));
        $post->message = $reply->message;
        $post->messageFormatted = $this->formatMessage($reply->message);

        if (!empty($reply->file_id)) {
            $post->file = $this->createFileClass($reply);
        }

        return $post;
    }

    public function delete(int $postId) : bool
    {
        $q = $this->db->prepare("INSERT INTO posts_deleted (id, user_id, board_id, thread_id, ip, time, subject, message, time_deleted)
            SELECT id, user_id, board_id, thread_id, ip, time, subject, message, NOW() FROM posts
            WHERE id = :post_id OR thread_id = :post_id_2");
        $q->bindValue('post_id', $postId);
        $q->bindValue('post_id_2', $postId);
        $q->execute();

        $q = $this->db->prepare("DELETE FROM posts WHERE id = :post_id LIMIT 1");
        $q->bindValue('post_id', $postId);
        $q->execute();

        return $q->rowCount() != 0;
    }

    public function updateThreadStats(int $threadId, string $key, int $val = 1) : bool
    {
        switch ($key) {
            case "replyCount":
                $column = 'reply_count';
                break;
            case "readCount":
                $column = 'read_count';
                break;
            default:
                return false;
        }

        $q = $this->db->prepare("INSERT INTO thread_statistics (thread_id, " . $column . ") VALUES (:thread_id, :val)
            ON DUPLICATE KEY UPDATE " . $column . " =  " . $column . "+:val_2");

        $q->bindValue('thread_id', $threadId);
        $q->bindValue('val', $val);
        $q->bindValue('val_2', $val);
        $q->execute();

        return true;
    }

    protected function getPostsQuery(string $append = '') : string
    {
        return "SELECT
            a.id, a.board_id, a.thread_id, user_id, ip, country_code, time, locked, sticky, username, subject, message,
            b.file_name AS file_display_name, c.id AS file_id, c.folder AS file_folder, c.name AS file_name,
            c.extension AS file_extension, c.size AS file_size, d.read_count, d.reply_count, d.distinct_reply_count
            FROM posts a
            LEFT JOIN posts_files b ON a.id = b.post_id
            LEFT JOIN files c ON b.file_id = c.id
            LEFT JOIN thread_statistics d ON a.id = d.thread_id " . $append;
    }

    protected function createFileClass($data) : File
    {
        $file = new File();
        $file->id = $data->file_id;
        $file->folder = $data->file_folder;
        $file->name = $data->file_name;
        $file->extension = $data->file_extension;
        $file->size = $data->file_size;
        $file->displayName = $data->file_display_name;

        return $file;
    }
}
