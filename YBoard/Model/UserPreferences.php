<?php
namespace YBoard\Model;

use YBoard\Abstracts\UserSubModel;

class UserPreferences extends UserSubModel
{
    public $theme = 'default';
    public $themeVariation = 0;
    public $locale = false;
    public $hideSidebar = false;

    protected $preferences;
    protected $toUpdate = [];

    public function __destruct()
    {
        // Delayed update to prevent unnecessary database queries
        if ($this->userId === null || empty($this->toUpdate)) {
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
    }

    public function set($keyName, $value) : bool
    {
        switch ($keyName) {
            case 'theme':
                $key = 1;
                break;
            case 'themeVariation':
                $key = 2;
                $value = (int)$value;
                break;
            case 'locale':
                $key = 3;
                break;
            case 'hideSidebar':
                $key = 4;
                $value = (int)$value;
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
                    $this->theme = $row->preferences_value;
                    break;
                case 2:
                    $this->themeVariation = (int)$row->preferences_value;
                    break;
                case 3:
                    $this->locale = $row->preferences_value;
                    break;
                case 4:
                    $this->hideSidebar = (bool)$row->preferences_value;
                    break;
                default:
                    continue;
            }
        }

        return true;
    }
}
