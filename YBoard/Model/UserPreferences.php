<?php
namespace YBoard\Model;

use YBoard\Model;
use YFW\Library\Database;

class UserPreferences extends Model
{
    public $theme = null;
    public $darkTheme = false;
    public $locale = false;
    public $hideSidebar = false;
    public $threadsPerPage = 10;
    public $repliesPerThread = 3;
    public $threadsPerCatalogPage = 100;
    public $hiddenNotificationTypes = [];

    // Gold functions
    public $hideAds = false;
    public $tinfoilMode = false;
    public $useName = false;

    protected $userId = null;
    protected $preferences;
    protected $toUpdate = [];

    protected $keyToName = [
        1 => 'theme',
        2 => 'darkTheme',
        3 => 'locale',
        4 => 'hideSidebar',
        5 => 'threadsPerPage',
        6 => 'repliesPerThread',
        7 => 'threadsPerCatalogPage',
        8 => 'hiddenNotificationTypes',
        9 => 'hideAds',
        10 => 'tinfoilMode',
        11 => 'useName',
    ];

    public function __construct(Database $db, ?int $userId = null, ?array $data = null)
    {
        parent::__construct($db);
        $this->userId = $userId;

        if ($data === null) {
            return;
        }

        foreach ($data as $pref) {
            if (!array_key_exists($pref->preferences_key, $this->keyToName)) {
                $this->reset($pref->preferences_key);
            }

            $keyName = $this->keyToName[$pref->preferences_key];
            switch ($keyName) {
                case 'theme':
                case 'locale':
                    $this->$keyName = $pref->preferences_value;
                    break;
                case 'darkTheme':
                case 'hideSidebar':
                case 'hideAds':
                case 'tinfoilMode':
                case 'useName':
                    $this->$keyName = (bool)$pref->preferences_value;
                    break;
                case 'threadsPerPage':
                case 'repliesPerThread':
                case 'threadsPerCatalogPage':
                    $this->$keyName = (int)$pref->preferences_value;
                    break;
                case 'hiddenNotificationTypes':
                    $this->$keyName = explode(',', $pref->preferences_value);
                    break;
            }
        }
    }

    public function __destruct()
    {
        // Delayed update to prevent unnecessary database queries
        if ($this->userId === null || empty($this->toUpdate)) {
            return;
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

    public function set(string $keyName, $value): bool
    {
        $key = array_search($keyName, $this->keyToName);
        if (!$key) {
            return false;
        }

        // Verify and filter values if needed
        switch ($key) {
            case 2: // Dark theme
            case 4: // Hide sidebar
                $value = $value === 'true' ? 1 : 0;
                break;
            case 5: // Threads per page
                if ($value > 50) {
                    $value = 50;
                }
                $value = (int)$value;
                break;
            case 6: // Replies per thread
                $value = (int)$value;
                if ($value > 10) {
                    $value = 10;
                }
                break;
            case 7: // Threads per catalog page
                $value = (int)$value;
                if ($value > 250) {
                    $value = 250;
                }
                break;
            case 8: // Hidden notification types
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

    public function reset($keyName): bool
    {
        $key = array_search($keyName, $this->keyToName);
        if ($key) {
            $defaults = new self($this->db);
            $this->$keyName = $defaults->$keyName;
        } else {
            $key = (int)$keyName;
        }

        $q = $this->db->prepare("DELETE FROM user_preferences WHERE preferences_key = :preferences_key AND user_id = :user_id");
        $q->bindValue(':preferences_key', $key, Database::PARAM_INT);
        $q->bindValue(':user_id', $this->userId, Database::PARAM_INT);
        $q->execute();

        return true;
    }

    public static function getByUser(Database $db, int $userId): self
    {
        $q = $db->prepare("SELECT preferences_key, preferences_value FROM user_preferences WHERE user_id = :user_id");
        $q->bindValue(':user_id', $userId, Database::PARAM_INT);
        $q->execute();

        if ($q->rowCount() === 0) {
            return new self($db, $userId);
        }

        return new self($db, $userId, $q->fetchAll());
    }
}
