<?php
namespace YBoard\Model;

use YFW\Library\Database;
use YBoard\Model;

class Log extends Model
{
    const ACTION_MOD_LOGIN = 1;
    const ACTION_MOD_POST_DELETE = 2;
    const ACTION_MOD_POST_FILE_DELETE = 3;
    const ACTION_MOD_ADD_BAN = 4;
    const ACTION_MOD_REPORT_CHECKED = 5;

    public static function write(Database $db, int $actionId, int $userId, $customData = null): bool
    {
        $q = $db->prepare("INSERT INTO log (action_id, user_id, custom_data, ip)
            VALUES (:action_id, :user_id, :custom_data, :ip)");
        $q->bindValue(':action_id', $actionId, Database::PARAM_INT);
        $q->bindValue(':user_id', $userId, Database::PARAM_INT);
        $q->bindValue(':custom_data', $customData);
        $q->bindValue(':ip', inet_pton($_SERVER['REMOTE_ADDR']));
        $q->execute();

        return true;
    }

    public static function getActionTitle(int $actionId): ?string
    {
        switch ($actionId) {
            case static::ACTION_MOD_LOGIN:
                return _('Moderator logged in');
            case static::ACTION_MOD_POST_DELETE:
                return _('Deleted a post');
            case static::ACTION_MOD_POST_FILE_DELETE:
                return _('Deleted a file from a post');
            case static::ACTION_MOD_ADD_BAN:
                return _('Added a ban');
            case static::ACTION_MOD_REPORT_CHECKED:
                return _('Checked a report');
        }

        return null;
    }
}
