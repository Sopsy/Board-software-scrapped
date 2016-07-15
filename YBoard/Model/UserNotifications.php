<?php
namespace YBoard\Model;

use YBoard\Data\Notification;
use YBoard\Library\Database;
use YBoard\Model;

class UserNotifications extends Model
{
    const NOTIFICATION_TYPE_POST_REPLY = 1;
    const NOTIFICATION_TYPE_THREAD_REPLY = 2;
    //const NOTIFICATION_TYPE_FOLLOWED_REPLY = 3;
    const NOTIFICATION_TYPE_GOT_TAG = 4;
    const NOTIFICATION_TYPE_GOT_GOLD = 5;
    const NOTIFICATION_TYPE_THREAD_SUPERSAGED = 6;
    const NOTIFICATION_TYPE_THREAD_FORCEBUMPED = 7;
    const NOTIFICATION_TYPE_THREAD_REVIVED = 8;
    const NOTIFICATION_TYPE_THREAD_AUTOLOCKED = 9;

    protected $userId;
    protected $hiddenTypes;

    public $list = [];
    public $unreadCount = 0;

    public function __construct(Database $db, int $userId = null, array $hiddenTypes = [], bool $skipLoad = false)
    {
        parent::__construct($db);
        $this->userId = $userId;
        $this->hiddenTypes = $hiddenTypes;

        if ($this->userId !== null && !$skipLoad) {
            $this->load();
        }
    }

    public function decrementCountByPostId($postId, $type = null)
    {
        if (is_array($postId)) {
            $eq = 'IN (:post_id)';
            $postId = implode(',', $postId);
        } else {
            $eq = '= :post_id';
        }

        $whereType = '';
        if ($type !== null) {
            if (is_array($type)) {
                $whereType = ' AND type IN (:type)';
                $type = implode(',', $type);
            } else {
                $whereType = ' AND type = :type';
            }
        }

        $q = $this->db->prepare("UPDATE user_notifications SET count = count-1 WHERE post_id " . $eq . $whereType);
        $q->bindValue('post_id', $postId);
        if ($type !== null) {
            $q->bindValue('type', $type);
        }
        $q->execute();

        if ($q->rowCount() == 0) {
            return false;
        }

        return true;
    }

    public function clearInvalid()
    {
        $q = $this->db->prepare("DELETE FROM user_notifications WHERE is_read = 0 and count = 0");
        $q->execute();

        return true;
    }

    public function add(int $userId, int $type, string $customData = null, int $postId = null)
    {
        $q = $this->db->prepare("INSERT INTO user_notifications (user_id, type, post_id, custom_data) 
            VALUES (:user_id, :type, :post_id, :custom_data)
            ON DUPLICATE KEY UPDATE is_read = 0, count = count+1");

        $q->bindValue('user_id', $userId);
        $q->bindValue('type', $type);
        $q->bindValue('custom_data', $customData);
        $q->bindValue('post_id', $postId);
        $q->execute();

        return true;
    }

    public function remove(int $notificationId) : bool
    {
        $q = $this->db->prepare("DELETE FROM user_notifications WHERE id = :id LIMIT 1");
        $q->bindValue('id', $notificationId);
        $q->execute();

        return true;
    }

    public function markRead(int $notificationId) : bool
    {
        $q = $this->db->prepare("UPDATE user_notifications SET count = 0, is_read = 1
            WHERE id = :id AND user_id = :user_id LIMIT 1");
        $q->bindValue('id', $notificationId);
        $q->bindValue('user_id', $this->userId);
        $q->execute();

        return true;
    }

    public function markReadByPost(int $postId) : bool
    {
        $q = $this->db->prepare("UPDATE user_notifications SET count = 0, is_read = 1
            WHERE post_id = :post_id AND user_id = :user_id LIMIT 1");
        $q->bindValue('post_id', $postId);
        $q->bindValue('user_id', $this->userId);
        $q->execute();

        return true;
    }

    public function markAllRead() : bool
    {
        $q = $this->db->prepare("UPDATE user_notifications SET count = 0, is_read = 1 WHERE user_id = :user_id");
        $q->bindValue('user_id', $this->userId);
        $q->execute();

        return true;
    }

    protected function load()
    {
        $notIn = '';
        if (!empty($this->hiddenTypes)) {
            $notIn = ' AND type NOT IN (:hidden_types)';
        }

        $q = $this->db->prepare("SELECT id, time, type, post_id, custom_data, count, is_read FROM user_notifications
            WHERE user_id = :user_id" . $notIn . " ORDER BY is_read ASC, time DESC LIMIT 100");
        $q->bindValue('user_id', $this->userId);
        if (!empty($this->hiddenTypes)) {
            $q->bindValue('hidden_types', implode(',', $this->hiddenTypes));
        }
        $q->execute();

        while ($row = $q->fetch()) {
            $notification = new Notification();
            $notification->id = $row->id;
            $notification->time = $row->time;
            $notification->type = $row->type;
            $notification->postId = $row->post_id;
            $notification->customData = $row->custom_data;
            $notification->count = $row->count == 0 ? 1 : $row->count;
            $notification->isRead = (bool)$row->is_read;

            switch ($notification->type) {
                case static::NOTIFICATION_TYPE_POST_REPLY:
                    $text = _('Your message has') . ' ';
                    if ($notification->count == 1) {
                        $text .= _('a new reply');
                    } else {
                        $text .= _('%d new replies');
                    }
                    break;
                case static::NOTIFICATION_TYPE_THREAD_REPLY:
                    $text = _('Your thread has') . ' ';
                    if ($notification->count == 1) {
                        $text .= _('a new reply');
                    } else {
                        $text .= _('%d new replies');
                    }
                    break;
                case static::NOTIFICATION_TYPE_GOT_TAG:
                    $text = _('You just got a new tag: %s!');
                    break;
                case static::NOTIFICATION_TYPE_GOT_GOLD:
                    $text = _('Someone just gave you a gold account');
                    if (!empty($notification->postId)) {
                        $text .= ' ' . _('for your post %s');
                    }
                    break;
                case static::NOTIFICATION_TYPE_THREAD_SUPERSAGED:
                    $text = _('Someone supersaged your thread...');
                    break;
                case static::NOTIFICATION_TYPE_THREAD_FORCEBUMPED:
                    $text = _('Someone force bumped your thread');
                    break;
                case static::NOTIFICATION_TYPE_THREAD_REVIVED:
                    $text = _('Someone revived your thread');
                    break;
                case static::NOTIFICATION_TYPE_THREAD_AUTOLOCKED:
                    $text = _('Your thread reached the reply limit');
                    break;
                default:
                    $text = _('Lolwut? Unknown notification!');
                    break;
            }
            $notification->text = $text;

            if (!$notification->isRead) {
                ++$this->unreadCount;
            }

            $this->list[] = $notification;
        }

        return true;
    }
}
