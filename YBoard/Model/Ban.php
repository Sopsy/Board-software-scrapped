<?php
namespace YBoard\Model;

use YBoard\Library\Database;
use YBoard\Model;
use YBoard\Traits\BanReasons;

class Ban extends Model
{
    use BanReasons;

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

    public function __construct(Database $db, $data)
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

    public function expire() : bool
    {
        $q = $this->db->prepare("UPDATE bans SET is_expired = 1 WHERE id = :id LIMIT 1");
        $q->bindValue('id', $this->id);
        $q->execute();

        $this->isExpired = 1;

        return true;
    }

    public function begin() : bool
    {
        $q = $this->db->prepare("UPDATE bans SET begin_time = NOW(), end_time = DATE_ADD(NOW(), INTERVAL length SECOND)
            WHERE id = :id LIMIT 1");
        $q->bindValue('id', $this->id);
        $q->execute();

        $this->beginTime = date('Y-m-d H:i:s');
        $this->endTime = date('Y-m-d H:i:s', time() + $this->length);

        return true;
    }

    public function getMessage()
    {
        if ($this->postId === null) {
            return false;
        }

        $posts = new Posts($this->db);
        $post = $posts->get($this->postId);

        if ($post !== false) {
            return $post->message;
        }

        $post = $posts->getDeletedMessage($this->postId);
        return $post;
    }
}
