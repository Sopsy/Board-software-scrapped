<?php
namespace YBoard\Model;

use YBoard\Library\Database;
use YBoard\Model;

class UserPreferences extends Model
{
    public $darkTheme = false;
    public $locale = false;

    protected $userId;
    protected $preferences;
    protected $toUpdate = [];

    public function __construct(Database $db, int $userId)
    {
        parent::__construct($db);
        $this->userId = $userId;
        $this->load();
    }

    public function __destruct()
    {
        // Delayed update to prevent unnecessary database queries
        if (empty($this->toUpdate)) {
            return true;
        }

        $query = str_repeat('(?,?,?),', count($this->toUpdate));
        $query = substr($query, 0, -1);

        $queryVars = [];
        foreach ($this->toUpdate as $key => $val) {
            $queryVars[] = (int)$this->userId;
            $queryVars[] = (int)$key;
            $queryVars[] = $val;
        }

        $q = $this->db->prepare("INSERT INTO user_preferences (user_id, preferences_key, preferences_value)
            VALUES " . $query . " ON DUPLICATE KEY UPDATE preferences_value = VALUES(preferences_value)");
        $q->execute($queryVars);

        return true;
    }

    public function set($keyName, $value) : bool
    {
        switch ($keyName) {
            case 'darkTheme':
                $key = 1;
                $value = $value ? 1 : 0;
                break;
            default:
                return false;
        }

        $this->toUpdate[$key] = $value;
        $this->{$key} = $value;

        return true;
    }

    protected function load() : bool
    {
        $q = $this->db->prepare("SELECT preferences_key, preferences_value FROM user_preferences WHERE user_id = :user_id");
        $q->bindValue('user_id', $this->userId);
        $q->execute();

        while ($row = $q->fetch()) {
            switch ($row->preferences_key) {
                case 1:
                    $this->darkTheme = (bool)$row->preferences_value;
                    break;
                case 2:
                    $this->locale = $row->preferences_value;
                    break;
                default:
                    continue;
            }
        }

        return true;
    }
}
