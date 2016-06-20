<?php
namespace YBoard\Model;

use YFW\Library\Database;
use YBoard\Model;

class PostReport extends Model
{
    use BanReasons;

    public $postId;
    public $additionalInfo;
    public $time;
    public $reportedBy;
    public $isChecked = false;
    public $checkedBy;
    public $post;

    public function __construct(Database $db, $data = [])
    {
        parent::__construct($db);

        foreach ($data as $key => $val) {
            switch ($key) {
                case 'post_id':
                    $this->postId = (int)$val;
                    break;
                case 'reason_id':
                    $this->reasonId = (int)$val;
                    break;
                case 'additional_info':
                    $this->additionalInfo = $val;
                    break;
                case 'time':
                    $this->time = $val;
                    break;
                case 'reported_by':
                    $this->reportedBy = inet_ntop($val);
                    break;
                case 'is_checked':
                    $this->isChecked = (bool)$val;
                    break;
                case 'checked_by':
                    $this->checkedBy = (int)$val;
                    break;
            }
        }

        $posts = new Post($this->db);
        if ($this->postId !== null) {
            $this->post = $posts->get($this->postId);
        }
    }

    public function setChecked(int $checkedBy): bool
    {
        $q = $this->db->prepare("UPDATE posts_reports SET is_checked = 1, checked_by = :checked_by
            WHERE post_id = :post_id LIMIT 1");
        $q->bindValue(':post_id', $this->postId);
        $q->bindValue(':checked_by', $checkedBy);
        $q->execute();

        return true;
    }

    public function getUnchecked(): array
    {
        $q = $this->db->prepare("SELECT post_id, reason_id, additional_info, time, reported_by FROM posts_reports
            WHERE is_checked = 0 ORDER BY reason_id ASC, time ASC");
        $q->execute();

        $reports = [];
        while ($row = $q->fetch()) {
            $reports[] = new PostReport($this->db, $row);
        }

        return $reports;
    }

    public function get(int $postId): ?self
    {
        $q = $this->db->prepare("SELECT post_id, reason_id, additional_info, time, reported_by FROM posts_reports
            WHERE post_id = :post_id LIMIT 1");
        $q->bindValue(':post_id', $postId);
        $q->execute();

        if ($q->rowCount() == 0) {
            return null;
        }

        $row = $q->fetch();

        return new self($this->db, $row);
    }

    public function getUncheckedCount(): int
    {
        $q = $this->db->prepare("SELECT COUNT(*) AS count FROM posts_reports WHERE is_checked = 0 LIMIT 1");
        $q->execute();

        return (int)$q->fetch()->count;
    }

    public function isReported(int $postId): bool
    {
        $q = $this->db->prepare("SELECT post_id FROM posts_reports WHERE post_id = :post_id AND is_checked = 0 LIMIT 1");
        $q->bindValue(':post_id', $postId);
        $q->execute();

        return $q->rowCount() != 0;
    }

    public function add(int $postId, int $reasonId, string $additionalInfo): bool
    {
        $q = $this->db->prepare("REPLACE INTO posts_reports (post_id, reason_id, additional_info, reported_by)
            VALUES (:post_id, :reason_id, :additional_info, :reported_by)");
        $q->bindValue(':post_id', $postId);
        $q->bindValue(':reason_id', $reasonId);
        $q->bindValue(':additional_info', $additionalInfo);
        $q->bindValue(':reported_by', inet_pton($_SERVER['REMOTE_ADDR']));
        $q->execute();

        return true;
    }
}
