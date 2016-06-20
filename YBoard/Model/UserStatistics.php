<?php
namespace YBoard\Model;

use YBoard\Model;
use YFW\Library\Database;

class UserStatistics extends Model
{
    public $pageLoads = 0;
    public $sentReplies = 0;
    public $createdThreads = 0;
    public $totalPosts = 0;
    public $uploadedFiles = 0;
    public $uploadedFilesTotalSize = 0;
    public $messageTotalCharacters = 0;
    public $messageAverageLength = 0.0;
    public $epicThreads = 0;
    public $purchasesTotalPrice = 0.0;
    public $goldAccountsDonated = 0;
    public $goldAccountsReceived = 0;
    public $adventWindows = 0;
    public $markoboyDonations = 0.0;
    public $niilo22Donations = 0.0;

    protected $userId = null;
    protected $statistics;
    protected $toUpdate = [];

    protected $keyToName = [
        1 => 'pageLoads',
        2 => 'sentReplies',
        3 => 'createdThreads',
        4 => 'uploadedFiles',
        5 => 'uploadedFilesTotalSize',
        6 => 'messageTotalCharacters',
        7 => 'epicThreads',
        8 => 'purchasesTotalPrice',
        9 => 'goldAccountsDonated',
        10 => 'goldAccountsReceived',

        1000 => 'markoboyDonations',
        1001 => 'niilo22Donations',
    ];

    public function __construct(Database $db, ?int $userId = null, ?array $data = null)
    {
        parent::__construct($db);
        $this->userId = $userId;

        if ($data === null) {
            return;
        }

        foreach ($data as $pref) {
            if (!array_key_exists($pref->statistics_key, $this->keyToName)) {
                $this->reset($pref->statistics_key);
            }

            $keyName = $this->keyToName[$pref->statistics_key];
            switch ($keyName) {
                case 'pageLoads':
                case 'sentReplies':
                case 'createdThreads':
                case 'uploadedFiles':
                case 'uploadedFilesTotalSize':
                case 'messageTotalCharacters':
                case 'epicThreads':
                case 'goldAccountsDonated':
                case 'goldAccountsReceived':
                    $this->$keyName = (int)$pref->statistics_value;
                    break;
                case 'purchasesTotalPrice':
                case 'markoboyDonations':
                case 'niilo22Donations':
                    $this->$keyName = (float)$pref->statistics_value;
                    break;
            }
        }

        $this->totalPosts = $this->createdThreads + $this->sentReplies;
        if ($this->totalPosts > 0) {
            $this->messageAverageLength = round($this->messageTotalCharacters / $this->totalPosts, 2);
        }
    }

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

        $q = $this->db->prepare("INSERT INTO user_statistics (user_id, statistics_key, statistics_value)
            VALUES " . $query . " ON DUPLICATE KEY UPDATE statistics_value = VALUES(statistics_value)");
        $q->execute($queryVars);
    }

    public function increment(string $keyName, int $incrementBy = 1): bool
    {
        $key = array_search($keyName, $this->keyToName);

        $this->{$keyName} += $incrementBy;
        $this->toUpdate[$key] = $this->{$keyName};

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
        $q = $db->prepare("SELECT statistics_key, statistics_value FROM user_statistics WHERE user_id = :user_id");
        $q->bindValue(':user_id', $userId, Database::PARAM_INT);
        $q->execute();

        if ($q->rowCount() === 0) {
            return new self($db, $userId);
        }

        return new self($db, $userId, $q->fetchAll());
    }
}
