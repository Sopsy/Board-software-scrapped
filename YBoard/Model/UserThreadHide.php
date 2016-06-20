<?php
namespace YBoard\Model;

use YBoard\Model;
use YFW\Library\Database;

class UserThreadHide extends Model
{
    public $threadId;
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
                case 'thread_id':
                    $this->threadId = (int)$val;
                    break;
            }
        }
    }

    public function delete(): bool
    {
        $q = $this->db->prepare("DELETE FROM user_thread_hide
            WHERE user_id = :user_id AND thread_id = :thread_id LIMIT 1");
        $q->bindValue(':user_id', $this->userId, Database::PARAM_INT);
        $q->bindValue(':thread_id', $this->threadId, Database::PARAM_INT);
        $q->execute();

        return true;
    }

    public static function create(Database $db, int $userId, int $threadId): self
    {
        $q = $db->prepare("INSERT IGNORE INTO user_thread_hide (user_id, thread_id) VALUES (:user_id, :thread_id)");
        $q->bindValue(':user_id', $userId, Database::PARAM_INT);
        $q->bindValue(':thread_id', $threadId, Database::PARAM_INT);
        $q->execute();

        $hidden = new self($db, $userId);
        $hidden->threadId = $threadId;

        return $hidden;
    }

    public static function getEmpty(): array
    {
        return [
            'list' => [],
        ];
    }

    public static function getByUser(Database $db, int $userId): array
    {
        $q = $db->prepare("SELECT thread_id FROM user_thread_hide WHERE user_id = :user_id");
        $q->bindValue(':user_id', $userId, Database::PARAM_INT);
        $q->execute();

        $threads = [];
        while ($data = $q->fetch()) {
            $threads[$data->thread_id] = new self($db, $userId, $data);
        }

        return [
            'list' => $threads,
        ];
    }
}
