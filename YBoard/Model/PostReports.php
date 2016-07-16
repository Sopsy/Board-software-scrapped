<?php
namespace YBoard\Model;

use YBoard\Model;

class PostReports extends Model
{
    const REASON_ILLEGAL = 1;
    const REASON_RULE_VIOLATION = 2;
    const REASON_PLEASE_REMOVE = 3;
    const REASON_OTHER = 4;

    public function getUnchecked()
    {

    }

    public function isReported(int $postId)
    {
        $q = $this->db->prepare("SELECT post_id FROM posts_reports WHERE post_id = :post_id LIMIT 1");
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

    public function getReasons() : array
    {
        $reasons = [
            static::REASON_ILLEGAL => _('Illegal content'),
            static::REASON_RULE_VIOLATION => _('Rule violation'),
            static::REASON_PLEASE_REMOVE => _('The content in this post is about me and I want it to be removed'),
            static::REASON_OTHER => _('Other, please specify below'),
        ];

        return $reasons;
    }
}
