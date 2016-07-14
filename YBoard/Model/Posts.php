<?php
namespace YBoard\Model;

use YBoard\Data\File;
use YBoard\Data\Post;
use YBoard\Data\Reply;
use YBoard\Data\Thread;
use YBoard\Data\ThreadStatistics;
use YBoard\Library\Database;
use YBoard\Library\Text;
use YBoard\Model;

class Posts extends Model
{
    protected $hiddenThreads = [];

    public function setHiddenThreads(array $hiddenThreads)
    {
        $this->hiddenThreads = $hiddenThreads;
    }

    public function getThreadMeta(int $id)
    {
        $q = $this->db->prepare("SELECT id, board_id, user_id, ip, country_code, time, locked, sticky
            FROM posts WHERE id = :id AND thread_id IS NULL LIMIT 1");
        $q->bindValue('id', (int)$id);
        $q->execute();

        if ($q->rowCount() == 0) {
            return false;
        }

        $row = $q->fetch();

        // Assign values to a class to return
        $thread = new Thread();
        $thread->id = $row->id;
        $thread->locked = (bool)$row->locked;
        $thread->boardId = $row->board_id;
        $thread->userId = $row->user_id;
        $thread->ip = inet_ntop($row->ip);
        $thread->countryCode = $row->country_code;
        $thread->time = $row->time;
        $thread->sticky = $row->sticky;

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

        $row = $q->fetch();
        $thread = $this->createThreadClass($row, 10000); // >= 10000 to get all replies

        return $thread;
    }

    public function getThreadsByUser(int $userId, int $limit = 1000) : array
    {
        $q = $this->db->prepare("SELECT id FROM posts
            WHERE user_id = ? AND thread_id IS NULL" . $this->getHiddenNotIn('id') . " LIMIT ?");

        $queryVars = $this->hiddenThreads;
        array_unshift($queryVars, $userId);
        array_push($queryVars, $limit);

        $q->execute($queryVars);

        if ($q->rowCount() == 0) {
            return [];
        }

        $threads = $q->fetchAll(Database::FETCH_COLUMN);

        return $threads;
    }

    public function getOldThreads(int $boardId, int $hours, int $limit = 100) : array
    {
        $q = $this->db->prepare("SELECT id FROM posts
            WHERE board_id = :board_id AND thread_id IS NULL AND bump_time < DATE_SUB(NOW(), INTERVAL :hours HOUR)
            AND sticky = 0
            LIMIT :limit");
        $q->bindValue('board_id', $boardId);
        $q->bindValue('hours', $hours);
        $q->bindValue('limit', $limit);
        $q->execute();

        if ($q->rowCount() == 0) {
            return [];
        }

        $threads = $q->fetchAll(Database::FETCH_COLUMN);

        return $threads;
    }

    public function getThreadsRepliedByUser(int $userId, int $limit = 1000) : array
    {
        $q = $this->db->prepare("SELECT DISTINCT thread_id AS thread_id FROM posts
            WHERE user_id = ? AND thread_id IS NOT NULL" . $this->getHiddenNotIn('thread_id') . " LIMIT ?");

        $queryVars = $this->hiddenThreads;
        array_unshift($queryVars, $userId);
        array_push($queryVars, $limit);

        $q->execute($queryVars);

        if ($q->rowCount() == 0) {
            return [];
        }

        $threads = $q->fetchAll(Database::FETCH_COLUMN);

        return $threads;
    }

    public function getCustomThreads(array $threadIds, int $page, int $count, int $replyCount = 0) : array
    {
        $limitStart = ($page - 1) * $count;

        if (count($threadIds) == 0) {
            return [];
        }

        $in = str_repeat('?,', count($threadIds));
        $in = substr($in, 0, -1);

        $q = $this->db->prepare($this->getPostsQuery("WHERE a.id IN (" . $in . ")
            ORDER BY bump_time DESC LIMIT " . (int)$limitStart . ', ' . (int)$count));
        $q->execute($threadIds);

        if ($q->rowCount() == 0) {
            return [];
        }

        $threads = [];

        while ($row = $q->fetch()) {
            // Assign values to a class to return
            $thread = $this->createThreadClass($row, $replyCount);
            $threads[] = $thread;
        }

        return $threads;
    }

    public function getBoardThreads(int $boardId, int $page, int $count, int $replyCount = 0) : array
    {
        $limitStart = ($page - 1) * $count;

        $q = $this->db->prepare($this->getPostsQuery("WHERE a.board_id = ? AND a.thread_id IS NULL
            " . $this->getHiddenNotIn('a.id') . "
            ORDER BY sticky DESC, bump_time DESC LIMIT " . (int)$limitStart . ', ' . (int)$count));

        $queryVars = $this->hiddenThreads;
        array_unshift($queryVars, $boardId);

        $q->execute($queryVars);

        if ($q->rowCount() == 0) {
            return [];
        }

        $threads = [];

        while ($row = $q->fetch()) {
            // Assign values to a class to return
            $thread = $this->createThreadClass($row, $replyCount);
            $threads[] = $thread;
        }

        return $threads;
    }

    public function getReplies(int $threadId, int $count = null, bool $newest = false, int $fromId = null) : array
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
        $q->bindValue('thread_id', $threadId);
        if ($from) {
            $q->bindValue('from', $fromId);
        }
        $q->execute();

        $replies = [];
        while ($row = $q->fetch()) {
            $row->thread_id = $threadId;
            $replies[] = $this->createReplyClass($row);
        }

        if ($newest) {
            $replies = array_reverse($replies);
        }

        return $replies;
    }

    protected function createSubject(string $message) : string
    {
        $subject = Text::stripFormatting($message);
        $subject = Text::truncate($subject, 40);
        $subject = trim($subject);

        return $subject;
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
        $q->bindValue('subject', empty($subject) ? null : $subject);
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

    public function removeFiles(int $postId) : bool
    {
        $q = $this->db->prepare("DELETE FROM posts_files WHERE post_id = :post_id");
        $q->bindValue('post_id', $postId);
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

        $row = $q->fetch();
        $post = $this->createReplyClass($row);

        return $post;
    }

    public function delete(int $postId) : bool
    {
        $q = $this->db->prepare("INSERT IGNORE INTO posts_deleted (id, user_id, board_id, thread_id, ip, time, subject, message, time_deleted)
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

    public function deleteMultiple(array $postIds) : bool
    {
        $count = count($postIds);
        if ($count == 0) {
            return true;
        } elseif ($count == 1) {
            return $this->delete($postIds[0]);
        }

        $in = str_repeat('?,', $count);
        $in = substr($in, 0, -1);

        $q = $this->db->prepare("INSERT IGNORE INTO posts_deleted (id, user_id, board_id, thread_id, ip, time, subject, message, time_deleted)
            SELECT id, user_id, board_id, thread_id, ip, time, subject, message, NOW() FROM posts
            WHERE id IN (" . $in . ") OR thread_id IN (" . $in . ")");
        $q->execute(array_merge($postIds, $postIds));

        $q = $this->db->prepare("DELETE FROM posts WHERE id IN (" . $in . ") LIMIT ?");
        $queryVars = $postIds;
        array_push($queryVars, $count);
        $q->execute($queryVars);

        return $q->rowCount() != 0;
    }

    public function deleteByUser(int $userId, int $intervalHours = 1000000) : bool
    {
        $q = $this->db->prepare("INSERT INTO posts_deleted (id, user_id, board_id, thread_id, ip, time, subject, message, time_deleted)
            SELECT id, user_id, board_id, thread_id, ip, time, subject, message, NOW() FROM posts
            WHERE user_id = :user_id AND time >= DATE_SUB(NOW(), INTERVAL :interval_hours HOUR)");
        $q->bindValue('user_id', $userId);
        $q->bindValue('interval_hours', $intervalHours);
        $q->execute();

        $q = $this->db->prepare("DELETE FROM posts
            WHERE user_id = :user_id AND time >= DATE_SUB(NOW(), INTERVAL :interval_hours HOUR)");
        $q->bindValue('user_id', $userId);
        $q->bindValue('interval_hours', $intervalHours);
        $q->execute();

        return true;
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

        $q->bindValue('thread_id', $threadId);
        $q->bindValue('val', $val);
        $q->bindValue('val_2', $val);
        $q->execute();

        return true;
    }

    public function setPostReplies(int $postId, array $replies, bool $clearOld = false) : bool
    {
        if (count($replies) == 0) {
            return true;
        }

        $query = str_repeat('(?,?),', count($replies));
        $query = substr($query, 0, -1);

        $queryVars = [];
        foreach ($replies as $repliedId) {
            $queryVars[] = $postId;
            $queryVars[] = $repliedId;
        }

        if ($clearOld) {
            $q = $this->db->prepare("DELETE FROM posts_replies WHERE post_id = :post_id");
            $q->bindValue('post_id', $postId);
            $q->execute($queryVars);
        }

        $q = $this->db->prepare("INSERT IGNORE INTO posts_replies (post_id, post_id_replied) VALUES " . $query);
        $q->execute($queryVars);

        return true;
    }

    protected function getHiddenNotIn(string $column) : string
    {
        $notIn = '';
        if (!empty($this->hiddenThreads)) {
            $notIn = ' AND ' . $column . ' NOT IN (';
            $notIn .= str_repeat('?,', count($this->hiddenThreads));
            $notIn = substr($notIn, 0, -1);
            $notIn .= ')';
        }

        return $notIn;
    }

    protected function getPostsQuery(string $append = '') : string
    {
        return "SELECT
            a.id, a.board_id, a.thread_id, user_id, ip, country_code, time, locked, sticky, username, subject, message,
            b.file_name AS file_display_name, c.id AS file_id, c.folder AS file_folder, c.name AS file_name,
            c.extension AS file_extension, c.size AS file_size, c.width AS file_width, c.height AS file_height,
            c.duration AS file_duration, c.has_thumbnail AS file_has_thumbnail, c.has_sound AS file_has_sound,
            c.is_gif AS file_is_gif, c.in_progress AS file_in_progress, d.read_count, d.reply_count,
            d.distinct_reply_count, e.url AS board_url,
            (SELECT GROUP_CONCAT(post_id) FROM posts_replies WHERE post_id_replied = a.id) AS post_replies
            FROM posts a
            LEFT JOIN posts_files b ON a.id = b.post_id
            LEFT JOIN files c ON b.file_id = c.id
            LEFT JOIN thread_statistics d ON a.id = d.thread_id
            LEFT JOIN boards e ON e.id = a.board_id
            " . $append;
    }

    protected function createThreadClass($data, int $replyCount) : Thread
    {
        if (empty($data->subject) && $data->subject != '0') {
            $data->subject = $this->createSubject($data->message);
        }

        $thread = new Thread();
        $thread->id = $data->id;
        $thread->locked = (bool)$data->locked;
        $thread->boardId = $data->board_id;
        $thread->boardUrl = $data->board_url;
        $thread->userId = $data->user_id;
        $thread->ip = inet_ntop($data->ip);
        $thread->countryCode = $data->country_code;
        $thread->time = $data->time;
        $thread->locked = $data->locked;
        $thread->sticky = $data->sticky;
        $thread->username = $data->username;
        $thread->subject = $data->subject;
        $thread->message = $data->message;
        if ($replyCount == 0) {
            $thread->threadReplies = false;
        } elseif ($replyCount >= 10000) {
            $thread->threadReplies = $this->getReplies($data->id);
        } else {
            $thread->threadReplies = $this->getReplies($data->id, $replyCount, true);
        }
        $thread->postReplies = !empty($data->post_replies) ? explode(',', $data->post_replies) : false;

        $thread->statistics = new ThreadStatistics();
        $thread->statistics->readCount = $data->read_count;
        $thread->statistics->replyCount = $data->reply_count;
        $thread->statistics->distinctReplyCount = $data->distinct_reply_count;

        if (!empty($data->file_id)) {
            $thread->file = $this->createFileClass($data);
        }

        return $thread;
    }

    protected function createReplyClass($data) : Reply
    {
        $reply = new Reply();
        $reply->id = $data->id;
        $reply->threadId = $data->thread_id;
        $reply->userId = $data->user_id;
        $reply->ip = inet_ntop($data->ip);
        $reply->countryCode = $data->country_code;
        $reply->username = $data->username;
        $reply->time = $data->time;
        $reply->message = $data->message;
        $reply->postReplies = !empty($data->post_replies) ? explode(',', $data->post_replies) : false;

        if (!empty($data->file_id)) {
            $reply->file = $this->createFileClass($data);
        }

        return $reply;
    }

    protected function createFileClass($data) : File
    {
        $file = new File();
        $file->id = $data->file_id;
        $file->folder = $data->file_folder;
        $file->name = $data->file_name;
        $file->extension = $data->file_extension;
        $file->size = $data->file_size;
        $file->width = $data->file_width;
        $file->height = $data->file_height;
        $file->displayName = $data->file_display_name;
        $file->duration = $data->file_duration;
        $file->hasThumbnail = $data->file_has_thumbnail;
        $file->hasSound = $data->file_has_sound;
        $file->isGif = $data->file_is_gif;
        $file->inProgress = $data->file_in_progress;

        return $file;
    }
}
