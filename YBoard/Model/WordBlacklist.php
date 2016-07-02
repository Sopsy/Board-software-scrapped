<?php
namespace YBoard\Model;

use YBoard\Model;

class WordBlacklist extends Model
{
    public $blacklist;

    public function match($str)
    {
        foreach ($this->getAll() as $word => $description) {
            error_log($word);
            if (stripos($str, $word) !== false) {
                return $description;
            }
        }

        return false;
    }

    public function getAll()
    {
        if ($this->blacklist) {
            return $this->blacklist;
        }

        $q = $this->db->query("SELECT word, reason_id FROM words_blacklist");
        $blacklist = [];
        while ($row = $q->fetch()) {
            $blacklist[$row->word] = $this->reasonToText($row->reason_id);
        }

        $this->blacklist = $blacklist;

        return $blacklist;
    }

    protected function reasonToText($reasonId)
    {
        switch($reasonId) {
            case 1:
                return _('URL shortener');
            case 2:
                return _('Spam');
            case 3:
                return _('Adverse content');
            default:
                return _('Blacklisted');
        }
    }
}
