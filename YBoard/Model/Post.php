<?php
namespace YBoard\Model;

use YBoard\Model;
use YFW\Library\Database;
use YFW\Library\Text;

class Post extends Model
{
    public $id;
    public $userId;
    public $ip;
    public $countryCode;
    public $boardId;
    public $threadId;
    public $time;
    public $username;
    public $message;
    public $postReplies = null;
    public $repliedPosts = null;
    public $file = null;

    protected const POST_QUERY = "SELECT
        a.id, a.board_id, a.thread_id, a.user_id, a.ip, a.country_code, a.time, a.locked, a.sticky, a.username,
        a.subject, a.message, b.file_name AS file_display_name, c.id AS file_id, c.folder AS file_folder,
        c.name AS file_name, c.extension AS file_extension, c.size AS file_size, c.width AS file_width,
        c.thumb_width AS file_thumb_width, c.thumb_height AS file_thumb_height,
        c.height AS file_height, c.duration AS file_duration, c.has_thumbnail AS file_has_thumbnail,
        c.has_sound AS file_has_sound, c.is_gif AS file_is_gif, c.in_progress AS file_in_progress, d.read_count,
        d.reply_count, d.distinct_reply_count, d.follow_count, d.hide_count, e.url AS board_url,
        (SELECT GROUP_CONCAT(CONCAT(post_id, '|', IFNULL(user_id, 0))) FROM post_reply WHERE post_id_replied = a.id) AS post_replies,
        (SELECT GROUP_CONCAT(CONCAT(post_id_replied, '|', IFNULL(user_id_replied, 0))) FROM post_reply WHERE post_id = a.id) AS replied_posts
        FROM post a
        LEFT JOIN post_file b ON a.id = b.post_id
        LEFT JOIN file c ON b.file_id = c.id
        LEFT JOIN post_statistics d ON a.id = d.thread_id
        LEFT JOIN board e ON e.id = a.board_id ";

    public function __construct(Database $db, \stdClass $data = null)
    {
        parent::__construct($db);

        if ($data !== null) {
            foreach ($data as $key => $val) {
                switch ($key) {
                    case 'id':
                        $this->id = (int)$val;
                        break;
                    case 'user_id':
                        $this->userId = $val;
                        break;
                    case 'ip':
                        $this->ip = inet_ntop($val);
                        break;
                    case 'country_code':
                        $this->countryCode = $val;
                        break;
                    case 'board_id':
                        $this->boardId = (int)$val;
                        break;
                    case 'thread_id':
                        $this->threadId = $val === null ? null : (int)$val;
                        break;
                    case 'time':
                        $this->time = $val;
                        break;
                    case 'username':
                        $this->username = $val;
                        break;
                    case 'message':
                        $this->message = $val;
                        break;
                    case 'post_replies':
                    case 'replied_posts':
                        $replies = empty($val) ? null : explode(',', $val);
                        if ($replies !== null) {
                            foreach ($replies as &$reply) {
                                $tmp = explode('|', $reply);
                                $reply = new \stdClass();
                                $reply->postId = (int)$tmp[0];
                                $reply->userId = $tmp[1] === null ? null : (int)$tmp[1];
                            }
                        }
                        if ($key === 'post_replies') {
                            $this->postReplies = $replies;
                        } else {
                            $this->repliedPosts = $replies;
                        }
                        break;
                }
            }
        }

        if (!empty($data->file_id)) {
            $this->file = new File($this->db, $data);
        }
    }

    public function delete(): bool
    {
        $q = $this->db->prepare("INSERT IGNORE INTO post_deleted (id, user_id, board_id, thread_id, ip, time, subject, message, time_deleted)
            SELECT id, user_id, board_id, thread_id, ip, time, subject, message, NOW() FROM post
            WHERE id = :post_id OR thread_id = :post_id_2");
        $q->bindValue(':post_id', $this->id, Database::PARAM_INT);
        $q->bindValue(':post_id_2', $this->id, Database::PARAM_INT);
        $q->execute();

        $q = $this->db->prepare("DELETE FROM post WHERE id = :post_id LIMIT 1");
        $q->bindValue(':post_id', $this->id, Database::PARAM_INT);
        $q->execute();

        return $q->rowCount() !== 0;
    }

    public function getRepliedPosts(): array
    {
        $q = $this->db->prepare("SELECT post_id_replied FROM post_reply WHERE post_id = :post_id");
        $q->bindValue(':post_id', $this->id, Database::PARAM_INT);
        $q->execute();

        return $q->fetchAll(Database::FETCH_COLUMN);
    }

    public function removeFiles(): bool
    {
        $q = $this->db->prepare("DELETE FROM post_file WHERE post_id = :post_id");
        $q->bindValue(':post_id', $this->id, Database::PARAM_INT);
        $q->execute();

        return true;
    }

    public function addFile(int $fileId, string $fileName): bool
    {
        $q = $this->db->prepare("INSERT INTO post_file (post_id, file_id, file_name)
            VALUES (:post_id, :file_id, :file_name)");
        $q->bindValue(':post_id', $this->id, Database::PARAM_INT);
        $q->bindValue(':file_id', $fileId, Database::PARAM_INT);
        $q->bindValue(':file_name', $fileName);
        $q->execute();

        return true;
    }

    public function setReplies(array $replies, bool $clearOld = false): bool
    {
        if (count($replies) == 0) {
            return true;
        }

        $replies = array_values($replies);

        $in = $this->db->buildIn($replies);
        $q = $this->db->prepare('SELECT id, user_id FROM post WHERE id IN (' . $in . ')');
        $q->execute($replies);

        $queryVars = [];
        $count = 0;
        while ($replied = $q->fetch()) {
            ++$count;
            $queryVars[] = $this->id;
            $queryVars[] = $replied->id;
            $queryVars[] = $this->userId;
            $queryVars[] = $replied->user_id;
        }

        if ($clearOld) {
            $q = $this->db->prepare("DELETE FROM post_reply WHERE post_id = :post_id");
            $q->bindValue(':post_id', $this->id, Database::PARAM_INT);
            $q->execute();
        }

        if ($count === 0) {
            return true;
        }

        $query = str_repeat('(?,?,?,?),', $count);
        $query = substr($query, 0, -1);

        $q = $this->db->prepare("INSERT IGNORE INTO post_reply (post_id, post_id_replied, user_id, user_id_replied) VALUES " . $query);
        $q->execute($queryVars);

        return true;
    }

    public function getFormattedMessage(?int $currentUserId = null): string
    {
        $message = Text::formatMessage($this->message);

        if ($this->repliedPosts !== null && $currentUserId !== null) {
            foreach ($this->repliedPosts as $reply) {
                $id = $reply->postId;
                $replace = '<a href="/post-' . $id . '" data-id="' . $id . '" class="ref">&gt;&gt;' . $id;
                if ($reply->userId === $currentUserId) {
                    $replace .= ' (' . _('You') . ')';
                } elseif ($reply->postId === $this->threadId) {
                    $replace .= ' (' . _('OP') . ')';
                }
                $replace .= '</a>';
                $message = str_replace('&gt;&gt;' . $id, $replace, $message);
            }
        }

        $message = preg_replace('/(^|[^>]+)(&gt;&gt;[0-9]+)/s', '$1<span class="invalid-ref">$2</span>', $message);

        return $message;
    }

    public static function get(Database $db, int $postId, bool $allData = true)
    {
        $wasArray = true;
        if (!is_array($postId)) {
            $wasArray = false;
            $postId = [$postId];
        }

        $in = $db->buildIn($postId);
        if ($allData) {
            $q = $db->prepare(static::POST_QUERY . 'WHERE a.id IN (' . $in . ')');
        } else {
            $q = $db->prepare("SELECT id, board_id, thread_id, user_id, ip, country_code, time, username
            FROM post WHERE id IN (" . $in . ")");
        }
        $q->execute($postId);

        if ($q->rowCount() == 0) {
            return null;
        }

        if (!$wasArray) {
            return new self($db, $q->fetch());
        }

        $posts = [];
        while ($row = $q->fetch()) {
            $posts[] = new self($db, $row);
        }

        return $posts;
    }

    public static function getDeleted(Database $db, int $postId): ?self
    {
        $q = $db->prepare("SELECT * FROM post_deleted WHERE id = :id LIMIT 1");
        $q->bindValue(':id', $postId, Database::PARAM_INT);
        $q->execute();

        if ($q->rowCount() == 0) {
            return null;
        }

        return new self($db, $q->fetch());
    }

    public static function deleteMany(Database $db, array $postIds): bool
    {
        $in = $db->buildIn($postIds);

        $q = $db->prepare("INSERT IGNORE INTO post_deleted (id, user_id, board_id, thread_id, ip, time, subject, message, time_deleted)
            SELECT id, user_id, board_id, thread_id, ip, time, subject, message, NOW() FROM post
            WHERE id IN (" . $in . ") OR thread_id IN (" . $in . ")");
        $q->execute(array_merge($postIds, $postIds));

        $q = $db->prepare("DELETE FROM post WHERE id IN (" . $in . ")");
        $q->execute($postIds);

        return $q->rowCount() != 0;
    }

    public static function deleteByUser(Database $db, int $userId, int $intervalHours = 1000000): bool
    {
        $q = $db->prepare("INSERT INTO post_deleted (id, user_id, board_id, thread_id, ip, time, subject, message, time_deleted)
            SELECT id, user_id, board_id, thread_id, ip, time, subject, message, NOW() FROM post
            WHERE user_id = :user_id AND time >= DATE_SUB(NOW(), INTERVAL :interval_hours HOUR)");
        $q->bindValue(':user_id', $userId, Database::PARAM_INT);
        $q->bindValue(':interval_hours', $intervalHours, Database::PARAM_INT);
        $q->execute();

        $q = $db->prepare("DELETE FROM post
            WHERE user_id = :user_id AND time >= DATE_SUB(NOW(), INTERVAL :interval_hours HOUR)");
        $q->bindValue(':user_id', $userId, Database::PARAM_INT);
        $q->bindValue(':interval_hours', $intervalHours, Database::PARAM_INT);
        $q->execute();

        return true;
    }
}
