<?php
namespace YBoard\Model;

use YBoard\Model;

class Log extends Model
{
    const ACTION_ID_MOD_LOGIN = 1;
    const ACTION_ID_MOD_POST_DELETE = 2;
    const ACTION_ID_MOD_POST_FILE_DELETE = 3;

    public function write(int $actionId, int $userId, $customData = null) : bool
    {
        $q = $this->db->prepare("INSERT INTO log (action_id, user_id, custom_data, ip)
            VALUES (:action_id, :user_id, :custom_data, :ip)");
        $q->bindValue('action_id', $actionId);
        $q->bindValue('user_id', $userId);
        $q->bindValue('custom_data', $customData);
        $q->bindValue('ip', inet_pton($_SERVER['REMOTE_ADDR']));
        $q->execute();

        return true;
    }
}
