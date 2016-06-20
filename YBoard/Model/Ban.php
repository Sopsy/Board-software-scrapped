<?php
namespace YBoard\Model;

use YFW\Library\Database;
use YBoard\Model;

class Ban extends Model
{
    use BanReasonTrait;

    const REASON_ILLEGAL = 1;
    const REASON_PLEASE_REMOVE = 2;
    const REASON_RULE_VIOLATION = 3;
    const REASON_OTHER = 4;

    public $id;
    public $userId;
    public $ip;
    public $length;
    public $beginTime;
    public $endTime;
    public $additionalInfo;
    public $postId;
    public $bannedBy;
    public $isExpired = false;
    public $isAppealed = false;
    public $appealText;
    public $appealIsChecked = false;
    public $messageFrom = false;

    public function __construct(Database $db, $data = [])
    {
        parent::__construct($db);

        foreach ($data as $key => $val) {
            switch ($key) {
                case 'id':
                    $this->id = (int)$val;
                    break;
                case 'user_id':
                    $this->userId = empty($val) ? null : (int)$val;
                    break;
                case 'ip':
                    $this->ip = empty($val) ? null : inet_ntop($val);
                    break;
                case 'begin_time':
                    $this->beginTime = empty($val) ? null : $val;
                    break;
                case 'length':
                    $this->length = empty($val) ? null : (int)$val;
                    break;
                case 'end_time':
                    $this->endTime = empty($val) ? null : $val;
                    break;
                case 'reason_id':
                    $this->reasonId = (int)$val;
                    break;
                case 'additional_info':
                    $this->additionalInfo = empty($val) ? null : $val;
                    break;
                case 'post_id':
                    $this->postId = empty($val) ? null : (int)$val;
                    break;
                case 'banned_by':
                    $this->bannedBy = (int)$val;
                    break;
                case 'is_expired':
                    $this->isExpired = (bool)$val;
                    break;
                case 'is_appealed':
                    $this->isAppealed = (bool)$val;
                    break;
                case 'appeal_text':
                    $this->appealText = empty($val) ? null : $val;
                    break;
                case 'appeal_is_checked':
                    $this->appealIsChecked = (bool)$val;
                    break;
            }
        }

        if (!$this->isExpired && !empty($this->endTime) && strtotime($this->endTime) < time()) {
            $this->expire();
        }

        $this->messageFrom = $this->getMessage();
    }

    public function expire(): bool
    {
        $q = $this->db->prepare("UPDATE ban SET is_expired = 1 WHERE id = :id LIMIT 1");
        $q->bindValue(':id', $this->id);
        $q->execute();

        $this->isExpired = 1;

        return true;
    }

    public function begin(): bool
    {
        $q = $this->db->prepare("UPDATE ban SET begin_time = NOW(), end_time = DATE_ADD(NOW(), INTERVAL length SECOND)
            WHERE id = :id LIMIT 1");
        $q->bindValue(':id', $this->id);
        $q->execute();

        $this->beginTime = date('Y-m-d H:i:s');
        $this->endTime = date('Y-m-d H:i:s', time() + $this->length);

        return true;
    }

    public function getMessage(): ?Post
    {
        if ($this->postId === null) {
            return null;
        }

        $post = Post::get($this->db, $this->postId);

        if ($post !== false) {
            return $post->message;
        }

        $post = Post::getDeleted($this->db, $this->postId);

        return $post;
    }

    public static function getReasons(bool $onlyBannable = false): array
    {
        $reportOnlyReasons = [
            static::REASON_PLEASE_REMOVE => [
                'name' => _('The content in this post is about me and I want it to be removed'),
            ],
        ];

        $bannableReasons = [
            static::REASON_ILLEGAL => [
                'name' => _('Illegal content'),
                'banLength' => 604800,
            ],
            static::REASON_RULE_VIOLATION => [
                'name' => _('Rule violation'),
                'banLength' => 86400,
            ],
            static::REASON_OTHER => [
                'name' => _('Other'),
                'banLength' => 3600,
            ],
        ];

        if (!$onlyBannable) {
            $reasons = $reportOnlyReasons + $bannableReasons;
        } else {
            $reasons = $bannableReasons;
        }

        return $reasons;
    }

    public static function get(Database $db, $ip, int $userId, $beginNow = true): ?self
    {
        $q = $db->prepare("SELECT id, user_id, ip, begin_time, end_time, reason_id, additional_info, post_id,
            banned_by, is_expired, is_appealed, appeal_text, appeal_is_checked
            FROM ban WHERE (ip = :ip OR user_id = :user_id) AND is_expired = 0 LIMIT 1");
        $q->bindValue(':ip', inet_pton($ip));
        $q->bindValue(':user_id', $userId);
        $q->execute();

        if ($q->rowCount() == 0) {
            return null;
        }

        $data = $q->fetch();
        $ban = new self($db, $data);

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
    ): bool {
        $q = $this->db->prepare("INSERT INTO ban (user_id, ip, length, reason_id, additional_info, post_id, banned_by)
            VALUES (:user_id, :ip, :length, :reason_id, :additional_info, :post_id, :banned_by)");
        $q->bindValue(':user_id', $userId);
        $q->bindValue(':ip', inet_pton($ip));
        $q->bindValue(':length', abs($length));
        $q->bindValue(':reason_id', $reasonId);
        $q->bindValue(':additional_info', $additionalInfo);
        $q->bindValue(':post_id', $postId);
        $q->bindValue(':banned_by', $bannedBy);
        $q->execute();

        return true;
    }

    public function getUncheckedAppealCount(): int
    {
        $q = $this->db->prepare("SELECT COUNT(*) AS count FROM ban
            WHERE is_appealed = 1 AND appeal_is_checked = 0 LIMIT 1");
        $q->execute();

        return (int)$q->fetch()->count;
    }
}
