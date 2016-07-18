<?php
namespace YBoard\Model;

use YBoard\Model;

class Bans extends Model
{
    const REASON_ILLEGAL = 1;
    const REASON_PLEASE_REMOVE = 2;
    const REASON_RULE_VIOLATION = 3;
    const REASON_OTHER = 4;

    public static function getReasons(bool $onlyBannable = false) : array
    {
        $reportOnlyReasons = [
            static::REASON_PLEASE_REMOVE => [
                'name' => _('The content in this post is about me and I want it to be removed'),
            ],
        ];

        $bannableReasons = [
            static::REASON_ILLEGAL => [
                'name' => _('Illegal content'),
                'banLength' => 604800
            ],
            static::REASON_RULE_VIOLATION => [
                'name' => _('Rule violation'),
                'banLength' => 86400
            ],
            static::REASON_OTHER => [
                'name' => _('Other'),
                'banLength' => 3600
            ],
        ];

        if (!$onlyBannable) {
            $reasons = $reportOnlyReasons + $bannableReasons;
        } else {
            $reasons = $bannableReasons;
        }

        return $reasons;
    }

    public function get($ip, int $userId, $beginNow = true)
    {
        $q = $this->db->prepare("SELECT id, user_id, ip, begin_time, end_time, reason_id, additional_info, post_id,
            banned_by, is_expired, is_appealed, appeal_text, appeal_is_checked
            FROM bans WHERE (ip = :ip OR user_id = :user_id) AND is_expired = 0 LIMIT 1");
        $q->bindValue('ip', inet_pton($ip));
        $q->bindValue('user_id', $userId);
        $q->execute();

        if ($q->rowCount() == 0) {
            return false;
        }

        $data = $q->fetch();
        $ban = new Ban($this->db, $data);

        if (empty($ban->beginTime) && $beginNow) {
            $ban->begin();
        }

        return $ban;
    }

    public function add(
        string $ip,
        int $userId,
        int $length,
        int $reasonId,
        $additionalInfo,
        $postId,
        int $bannedBy
    ) : bool
    {
        $q = $this->db->prepare("INSERT INTO bans (user_id, ip, length, reason_id, additional_info, post_id, banned_by)
            VALUES (:user_id, :ip, :length, :reason_id, :additional_info, :post_id, :banned_by)");
        $q->bindValue('user_id', $userId);
        $q->bindValue('ip', inet_pton($ip));
        $q->bindValue('length', abs($length));
        $q->bindValue('reason_id', $reasonId);
        $q->bindValue('additional_info', $additionalInfo);
        $q->bindValue('post_id', $postId);
        $q->bindValue('banned_by', $bannedBy);
        $q->execute();

        return true;
    }

    public function getUncheckedAppealCount()
    {
        $q = $this->db->prepare("SELECT COUNT(*) AS count FROM bans
            WHERE is_appealed = 1 AND appeal_is_checked = 0 LIMIT 1");
        $q->execute();

        return (int) $q->fetch()->count;
    }
}
