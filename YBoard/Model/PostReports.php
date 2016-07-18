<?php
namespace YBoard\Model;

use YBoard\Model;

class PostReports extends Model
{
    public function getUnchecked() : array
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

    public function get(int $postId)
    {
        $q = $this->db->prepare("SELECT post_id, reason_id, additional_info, time, reported_by FROM posts_reports
            WHERE post_id = :post_id LIMIT 1");
        $q->bindValue('post_id', $postId);
        $q->execute();

        if ($q->rowCount() == 0) {
            return false;
        }

        $row = $q->fetch();
        return new PostReport($this->db, $row);
    }

    public function getUncheckedCount() : int
    {
        $q = $this->db->prepare("SELECT COUNT(*) AS count FROM posts_reports WHERE is_checked = 0 LIMIT 1");
        $q->execute();

        return (int) $q->fetch()->count;
    }

    public function isReported(int $postId)
    {
        $q = $this->db->prepare("SELECT post_id FROM posts_reports WHERE post_id = :post_id AND is_checked = 0 LIMIT 1");
        $q->bindValue('post_id', $postId);
        $q->execute();

        return $q->rowCount() != 0;
    }

    public function add(int $postId, int $reasonId, $additionalInfo) : bool
    {
        $q = $this->db->prepare("REPLACE INTO posts_reports (post_id, reason_id, additional_info, reported_by)
            VALUES (:post_id, :reason_id, :additional_info, :reported_by)");
        $q->bindValue('post_id', $postId);
        $q->bindValue('reason_id', $reasonId);
        $q->bindValue('additional_info', $additionalInfo);
        $q->bindValue('reported_by', inet_pton($_SERVER['REMOTE_ADDR']));
        $q->execute();

        return true;
    }
}
