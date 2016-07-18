<?php
namespace YBoard\Model;

use YBoard\Library\Database;
use YBoard\Model;
use YBoard\Traits\BanReasons;

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

        $posts = new Posts($this->db);
        if ($this->postId !== null) {
            $this->post = $posts->get($this->postId);
        }
    }

    public function setChecked(int $checkedBy) : bool
    {
        $q = $this->db->prepare("UPDATE posts_reports SET is_checked = 1, checked_by = :checked_by
            WHERE post_id = :post_id LIMIT 1");
        $q->bindValue('post_id', $this->postId);
        $q->bindValue('checked_by', $checkedBy);
        $q->execute();

        return true;
    }
}
