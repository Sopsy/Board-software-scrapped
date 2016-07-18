<?php
namespace YBoard\Traits;

use YBoard\Model\Bans;

trait BanReasons
{
    public $reasonId;

    public function getReasonText() : string
    {
        foreach (Bans::getReasons() as $reasonId => $reason) {
            if ($this->reasonId == $reasonId) {
                return $reason['name'];
            }
        }

        return '';
    }
}
