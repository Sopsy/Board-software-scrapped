<?php

namespace YBoard;

use YBoard\Library\Database;

abstract class Model
{
    protected $db;

    public function __construct(Database $db)
    {
        $this->db = $db;
    }

}
