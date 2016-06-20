<?php

namespace YBoard\Model;

use YBoard;
use Library\DbConnection;

class Example extends YBoard\Model
{
    public function getSomething()
    {
        $something = [1,2,3];

        return $something;
    }
}
