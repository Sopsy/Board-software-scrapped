<?php
namespace YBoard\Model;

use YBoard\Model;
use YFW\Library\Database;

class UserNotification extends Model
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

    public $id;
    public $time;
    public $type;
    public $postId;
    public $customData;
    public $count;
    public $isRead;
    public $text;

    public function __construct(Database $db, ?\stdClass $data = null)
    {
        parent::__construct($db);

        if ($data === null) {
            return;
        }

        foreach ($data as $key => $val) {
            switch ($key) {
                case 'id':
                    $this->id = (int)$val;
                    break;
                case 'time':
                case 'type':
                    $this->$key = $val;
                    break;
                case 'post_id':
                    $this->postId = (int)$val;
                    break;
                case 'custom_data':
                    $this->customData = $val;
                    break;
                case 'count':
                    $this->count = (int)$val === 0 ? 1 : $val;
                    break;
                case 'is_read':
                    $this->isRead = (bool)$val;
                    break;
            }
        }

        $this->text = static::getTitle($this);
    }

    public static function decrementCountByPostId(Database $db, int $postId, int $type = null): bool
    {
        if (!is_array($postId)) {
            $postId = [$postId];
        }

        $params = $postId;

        $whereType = '';
        if ($type !== null) {
            if (!is_array($type)) {
                $type = [$type];
            }

            $params = array_merge($postId, $type);
            $whereType = ' AND type IN (' . $db->buildIn($type) . ')';
        }

        $q = $db->prepare("UPDATE user_notification SET count = count-1
            WHERE post_id IN (" . $db->buildIn($postId) . ")" . $whereType);
        $q->execute($params);

        if ($q->rowCount() == 0) {
            return false;
        }

        return true;
    }

    public function markRead(): bool
    {
        $q = $this->db->prepare("UPDATE user_notification SET count = 0, is_read = 1
            WHERE id = :id AND is_read = 0 LIMIT 1");
        $q->bindValue(':id', $this->id, Database::PARAM_INT);
        $q->execute();

        return true;
    }

    public function delete(): bool
    {
        $q = $this->db->prepare("DELETE FROM user_notification WHERE id = :id LIMIT 1");
        $q->bindValue(':id', $this->id, Database::PARAM_INT);
        $q->execute();

        return true;
    }

    public static function create(
        Database $db,
        int $userId,
        int $type,
        ?string $customData = null,
        ?int $postId = null
    ): self {
        $q = $db->prepare("INSERT INTO user_notification (user_id, type, post_id, custom_data) 
            VALUES (:user_id, :type, :post_id, :custom_data)
            ON DUPLICATE KEY UPDATE is_read = 0, count = count+1");
        $q->bindValue(':user_id', $userId, Database::PARAM_INT);
        $q->bindValue(':type', $type, Database::PARAM_INT);
        $q->bindValue(':custom_data', $customData);
        $q->bindValue(':post_id', $postId, Database::PARAM_INT);
        $q->execute();

        $notification = new self($db);
        $notification->id = $db->lastInsertId();
        $notification->type = $type;
        $notification->customData = $customData;
        $notification->postId = $postId;

        return $notification;
    }

    public static function markReadByThread(Database $db, int $userId, int $threadId): bool
    {
        $q = $db->prepare("UPDATE user_notification SET is_read = 1, count = 0
            WHERE user_id = :user_id AND post_id IN (SELECT id FROM post WHERE thread_id = :thread_id) AND is_read = 0");
        $q->bindValue(':thread_id', $threadId, Database::PARAM_INT);
        $q->bindValue(':user_id', $userId, Database::PARAM_INT);
        $q->execute();

        return true;
    }

    public static function markReadByPost(Database $db, int $userId, int $postId): bool
    {
        $q = $db->prepare("UPDATE user_notification SET count = 0, is_read = 1
            WHERE post_id = :post_id AND user_id = :user_id AND is_read = 0 LIMIT 1");
        $q->bindValue(':post_id', $postId, Database::PARAM_INT);
        $q->bindValue(':user_id', $userId, Database::PARAM_INT);
        $q->execute();

        return true;
    }

    public static function markReadByUser(Database $db, int $userId): bool
    {
        $q = $db->prepare("UPDATE user_notification SET count = 0, is_read = 1
            WHERE user_id = :user_id AND is_read = 0");
        $q->bindValue(':user_id', $userId, Database::PARAM_INT);
        $q->execute();

        return true;
    }

    public static function clearInvalid(Database $db): bool
    {
        $q = $db->prepare("DELETE FROM user_notification WHERE is_read = 0 AND count = 0");
        $q->execute();

        return true;
    }

    public static function get(Database $db, int $id): ?self
    {
        $q = $db->prepare("SELECT id, time, type, post_id, custom_data, count, is_read FROM user_notification
            WHERE id = :id LIMIT 1");
        $q->bindValue(':id', $id);
        $q->execute();

        if ($q->rowCount() === 0) {
            return null;
        }

        return new self($db, $q->fetch());
    }

    public static function getByUser(Database $db, int $userId, ?array $hiddenTypes = null): array
    {
        $params = [$userId];
        $notIn = '';
        if (!empty($hiddenTypes)) {
            $params = array_merge($params, $hiddenTypes);
            $notIn = ' AND type NOT IN (' . $db->buildIn($hiddenTypes) . ')';
        }

        $q = $db->prepare("SELECT id, time, type, post_id, custom_data, count, is_read FROM user_notification
            WHERE user_id = ?" . $notIn . " ORDER BY is_read ASC, TIME DESC LIMIT 100");
        $q->execute($params);

        $unreadCount = 0;
        $notifications = [];
        while ($row = $q->fetch()) {
            $notifications[] = new self($db, $row);
            if ($row->is_read === 0) {
                ++$unreadCount;
            }
        }

        return ['unread' => $unreadCount, 'list' => $notifications];
    }

    protected function getTitle(self $notification): string
    {
        switch ($notification->type) {
            case static::NOTIFICATION_TYPE_POST_REPLY:
                $text = _('Your post has') . ' ';
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
                $text = _('Someone just gave you a gold account!');
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

        return $text;
    }
}
