<?php
namespace YBoard\Model;

use YBoard\Library\Database;
use YBoard\Model;

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
    public $postReplies;
    public $file;

    public function __construct(Database $db, $data = [])
    {
        parent::__construct($db);

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
                    $this->threadId = (int)$val;
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
                    $this->postReplies = empty($val) ? null : explode(',', $val);
                    break;
            }
        }

        if (!empty($data->file_id)) {
            $this->file = new File($this->db, $data);
        }
    }

    public function delete() : bool
    {
        $q = $this->db->prepare("INSERT IGNORE INTO posts_deleted (id, user_id, board_id, thread_id, ip, time, subject, message, time_deleted)
            SELECT id, user_id, board_id, thread_id, ip, time, subject, message, NOW() FROM posts
            WHERE id = :post_id OR thread_id = :post_id_2");
        $q->bindValue('post_id', $this->id);
        $q->bindValue('post_id_2', $this->id);
        $q->execute();

        $q = $this->db->prepare("DELETE FROM posts WHERE id = :post_id LIMIT 1");
        $q->bindValue('post_id', $this->id);
        $q->execute();

        return $q->rowCount() != 0;
    }

    public function getRepliedPosts() : array
    {
        $q = $this->db->prepare("SELECT post_id_replied FROM posts_replies WHERE post_id = :post_id");
        $q->bindValue('post_id', $this->id);
        $q->execute();

        return $q->fetchAll(Database::FETCH_COLUMN);
    }

    public function removeFiles() : bool
    {
        $q = $this->db->prepare("DELETE FROM posts_files WHERE post_id = :post_id");
        $q->bindValue('post_id', $this->id);
        $q->execute();

        return true;
    }

    public function addFile(int $fileId, string $fileName) : bool
    {
        $q = $this->db->prepare("INSERT INTO posts_files (post_id, file_id, file_name)
            VALUES (:post_id, :file_id, :file_name)");
        $q->bindValue('post_id', $this->id);
        $q->bindValue('file_id', $fileId);
        $q->bindValue('file_name', $fileName);
        $q->execute();

        return true;
    }

    public function setReplies(array $replies, bool $clearOld = false) : bool
    {
        if (count($replies) == 0) {
            return true;
        }

        $query = str_repeat('(?,?),', count($replies));
        $query = substr($query, 0, -1);

        $queryVars = [];
        foreach ($replies as $repliedId) {
            $queryVars[] = $this->id;
            $queryVars[] = $repliedId;
        }

        if ($clearOld) {
            $q = $this->db->prepare("DELETE FROM posts_replies WHERE post_id = :post_id");
            $q->bindValue('post_id', $this->id);
            $q->execute();
        }

        $q = $this->db->prepare("INSERT IGNORE INTO posts_replies (post_id, post_id_replied) VALUES " . $query);
        $q->execute($queryVars);

        return true;
    }
}
