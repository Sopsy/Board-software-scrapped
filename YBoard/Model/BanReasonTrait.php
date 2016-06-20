<?php
namespace YBoard\Model;

trait BanReasonTrait
{
    public $reasonId;

    public function getReasonText() : ?string
    {
        foreach (Ban::getReasons() as $reasonId => $reason) {
            if ($this->reasonId == $reasonId) {
                return $reason['name'];
            }
        }

        return null;
    }
}
