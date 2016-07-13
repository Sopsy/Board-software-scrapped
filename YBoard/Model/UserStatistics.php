<?php
namespace YBoard\Model;

use YBoard\Abstracts\UserSubModel;

class UserStatistics extends UserSubModel
{
    public $pageLoads = 0;
    public $sentReplies = 0;
    public $createdThreads = 0;
    public $totalPosts = 0;
    public $uploadedFiles = 0;
    public $uploadedFilesTotalSize = 0;
    public $messageTotalCharacters = 0;
    public $messageAverageLength = 0;
    public $epicThreads = 0;
    public $purchasesTotalPrice = 0;
    public $goldAccountsDonated = 0;
    public $goldAccountsReceived = 0;
    public $adventWindows = 0;

    public $markoboyDonations = 0;
    public $niilo22Donations = 0;

    protected $statistics;
    protected $toUpdate = [];
    protected $keyNames = [
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
        11 => 'adventWindows',

        1000 => 'markoboyDonations',
        1001 => 'niilo22Donations',
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

        $q = $this->db->prepare("INSERT INTO user_statistics (user_id, statistics_key, statistics_value)
            VALUES " . $query . " ON DUPLICATE KEY UPDATE statistics_value = VALUES(statistics_value)");
        $q->execute($queryVars);
    }

    public function increment(string $keyName, int $incrementBy = 1) : bool
    {
        $key = array_search($keyName, $this->keyNames);

        $this->{$keyName} += $incrementBy;
        $this->toUpdate[$key] = $this->{$keyName};

        return true;
    }

    protected function load() : bool
    {
        $q = $this->db->prepare("SELECT statistics_key, statistics_value FROM user_statistics WHERE user_id = :user_id");
        $q->bindValue('user_id', $this->userId);
        $q->execute();

        while ($row = $q->fetch()) {
            if (!array_key_exists($row->statistics_key, $this->keyNames)) {
                continue;
            }

            $this->{$this->keyNames[$row->statistics_key]} = $row->statistics_value;
        }

        $this->totalPosts = $this->createdThreads + $this->sentReplies;
        $this->purchasesTotalPrice = str_replace(',', '.', $this->purchasesTotalPrice / 100);

        if ($this->totalPosts != 0) {
            $this->messageAverageLength = round($this->messageTotalCharacters / $this->totalPosts);
        }

        return true;
    }
}
