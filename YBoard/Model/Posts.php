<?php
namespace YBoard\Model;

use YBoard\Library\Database;
use YBoard\Model;
use YBoard\Traits\PostsQuery;

class Posts extends Model
{
    use PostsQuery;

    protected $hiddenThreads = [];

    public function setHiddenThreads(array $hiddenThreads)
    {
        $this->hiddenThreads = $hiddenThreads;
    }

    public function getThread(int $id, bool $allData = true)
    {
        if ($allData) {
            $q = $this->db->prepare($this->getPostsQuery("WHERE a.id = :id AND a.thread_id IS NULL LIMIT 1"));
        } else {
            $q = $this->db->prepare("SELECT id, board_id, user_id, ip, country_code, time, locked, sticky
            FROM posts WHERE id = :id AND thread_id IS NULL LIMIT 1");
        }
        $q->bindValue('id', (int)$id);
        $q->execute();

        if ($q->rowCount() == 0) {
            return false;
        }

        $row = $q->fetch();
        $thread = new Thread($this->db, $row, ($allData ? 10000 : false)); // >= 10000 to get all replies

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

    public function getCustomThreads(
        array $threadIds,
        int $page,
        int $count,
        int $replyCount = 0,
        $keepOrder = false
    ) : array
    {
        $limitStart = ($page - 1) * $count;

        if (count($threadIds) == 0) {
            return [];
        }

        $in = $this->db->buildIn($threadIds);

        $order = '';
        if ($keepOrder) {
            $order = ' FIELD(a.id, ' . $in . '),';
            $threadIds = array_merge($threadIds, $threadIds);
        }

        $q = $this->db->prepare($this->getPostsQuery("WHERE a.id IN (" . $in . ")
            ORDER BY" . $order . " bump_time DESC LIMIT " . (int)$limitStart . ', ' . (int)$count));
        $q->execute($threadIds);

        if ($q->rowCount() == 0) {
            return [];
        }

        $threads = [];

        while ($row = $q->fetch()) {
            // Assign values to a class to return
            $thread = new Thread($this->db, $row, $replyCount);
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
            $thread = new Thread($this->db, $row, $replyCount);
            $threads[] = $thread;
        }

        return $threads;
    }

    public function createThread(
        int $userId,
        int $boardId,
        string $subject,
        string $message,
        $username,
        string $countryCode
    ) : Thread
    {
        $q = $this->db->prepare("INSERT INTO posts
            (user_id, board_id, ip, country_code, username, subject, message, bump_time, locked, sticky)
            VALUES (:user_id, :board_id, :ip, :country_code, :username, :subject, :message, NOW(), 0, 0)
        ");
        $q->bindValue('user_id', $userId);
        $q->bindValue('board_id', $boardId);
        $q->bindValue('ip', inet_pton($_SERVER['REMOTE_ADDR']));
        $q->bindValue('country_code', $countryCode);
        $q->bindValue('username', $username);
        $q->bindValue('subject', empty($subject) ? null : $subject);
        $q->bindValue('message', $message);
        $q->execute();

        $thread = new Thread($this->db, [], false);
        $thread->id = $this->db->lastInsertId();

        return $thread;
    }

    public function get($postId, bool $allData = true)
    {
        $wasArray = true;
        if (!is_array($postId)) {
            $wasArray = false;
            $postId = [$postId];
        }

        $in = $this->db->buildIn($postId);
        if ($allData) {
            $q = $this->db->prepare($this->getPostsQuery('WHERE a.id IN (' . $in . ')'));
        } else {
            $q = $this->db->prepare("SELECT id, board_id, thread_id, user_id, ip, country_code, time, username
            FROM posts WHERE id IN (" . $in . ")");
        }
        $q->execute($postId);

        if ($q->rowCount() == 0) {
            return false;
        }

        if (!$wasArray) {
            return new Post($this->db, $q->fetch());
        }

        $posts = [];
        while ($row = $q->fetch()) {
            $posts[] = new Post($this->db, $row);
        }

        return $posts;
    }

    public function deleteMany(array $postIds) : bool
    {
        $in = $this->db->buildIn($postIds);

        $q = $this->db->prepare("INSERT IGNORE INTO posts_deleted (id, user_id, board_id, thread_id, ip, time, subject, message, time_deleted)
            SELECT id, user_id, board_id, thread_id, ip, time, subject, message, NOW() FROM posts
            WHERE id IN (" . $in . ") OR thread_id IN (" . $in . ")");
        $q->execute(array_merge($postIds, $postIds));

        $q = $this->db->prepare("DELETE FROM posts WHERE id IN (" . $in . ")");
        $q->execute($postIds);

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
}
