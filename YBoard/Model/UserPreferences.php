<?php
namespace YBoard\Model;

use YBoard\Abstracts\UserSubModel;

class UserPreferences extends UserSubModel
{
    public $theme = 'default';
    public $themeVariation = 0;
    public $locale = false;
    public $hideSidebar = false;
    public $threadsPerPage = 10;
    public $repliesPerThread = 3;
    public $threadsPerCatalogPage = 100;
    public $hiddenNotificationTypes = [];

    protected $preferences;
    protected $toUpdate = [];

    protected $keyToName = [
        1 => 'theme',
        2 => 'themeVariation',
        3 => 'locale',
        4 => 'hideSidebar',
        5 => 'threadsPerPage',
        6 => 'repliesPerThread',
        7 => 'threadsPerCatalogPage',
        8 => 'hiddenNotificationTypes',
    ];

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
        if (!array_search($keyName, $this->keyToName)) {
            return false;
        }

        $key = array_search($keyName, $this->keyToName);

        // Verify and filter values if needed
        switch ($key) {
            case 4:
                $value = (int)$value;
                break;
            case 5:
                if ($value > 50) {
                    $value = 50;
                }
                $value = (int)$value;
                break;
            case 6:
                $value = (int)$value;
                if ($value > 10) {
                    $value = 10;
                }
                break;
            case 7:
                $value = (int)$value;
                if ($value > 250) {
                    $value = 250;
                }
                break;
            case 8:
                foreach ($value as &$v) {
                    $v = (int)$v;
                }
                $value = implode(',', $value);
                break;
        }

        $this->toUpdate[$key] = $value;
        $this->{$keyName} = $value;

        return true;
    }

    public function reset($keyName)
    {
        if (array_search($keyName, $this->keyToName)) {
            $key = array_search($keyName, $this->keyToName);
            $defaults = new self($this->db);
            $this->{$keyName} = $defaults->{$keyName};
        } else {
            $key = (int)$keyName;
        }

        $q = $this->db->prepare("DELETE FROM user_preferences WHERE preferences_key = :preferences_key AND user_id = :user_id");
        $q->bindValue('preferences_key', $key);
        $q->bindValue('user_id', $this->userId);
        $q->execute();

        return true;
    }

    protected function load() : bool
    {
        $q = $this->db->prepare("SELECT preferences_key, preferences_value FROM user_preferences WHERE user_id = :user_id");
        $q->bindValue('user_id', $this->userId);
        $q->execute();

        while ($row = $q->fetch()) {
            if (!array_key_exists($row->preferences_key, $this->keyToName)) {
                $this->reset($row->preferences_key);
                continue;
            }
            $keyName = $this->keyToName[$row->preferences_key];
            $value = $row->preferences_value;

            switch ($row->preferences_key) {
                case 2:
                case 5:
                case 6:
                case 7:
                    $value = (int)$value;
                    break;
                case 4:
                    $value = (bool)$value;
                    break;
                case 8:
                    $value = explode(',', $value);
                    break;
            }
            $this->{$keyName} = $value;
        }

        return true;
    }
}
