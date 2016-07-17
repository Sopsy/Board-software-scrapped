<?php
namespace YBoard\Model;

use YBoard\Library\Database;
use YBoard\Model;

class UserSession extends Model
{
    public $id;
    public $userId;
    public $csrfToken;
    public $ip;
    public $loginTime;
    public $lastActive;

    public function __construct(Database $db, $data = [])
    {
        parent::__construct($db);

        foreach ($data as $key => $val) {
            switch ($key) {
                case 'id':
                    $this->id = (int)$val;
                    break;
                case 'user_id':
                    $this->userId = (int)$val;
                    break;
                case 'csrf_token':
                    $this->csrfToken = bin2hex($val);
                    break;
                case 'ip':
                    $this->ip = $val;
                    break;
                case 'login_time':
                    $this->loginTime = $val;
                    break;
                case 'last_active':
                    $this->lastActive = $val;
                    break;
            }
        }
    }

    public function updateLastActive() : bool
    {
        if ($this->id === null) {
            return false;
        }

        $q = $this->db->prepare("UPDATE user_sessions SET last_active = NOW(), ip = :ip WHERE id = :id LIMIT 1");
        $q->bindValue('id', $this->id);
        $q->bindValue('ip', inet_pton($_SERVER['REMOTE_ADDR']));
        $q->execute();

        return true;
    }

    public function destroy() : bool
    {
        $q = $this->db->prepare("DELETE FROM user_sessions WHERE id = :id LIMIT 1");
        $q->bindValue('id', $this->id);
        $q->execute();

        return true;
    }
}
