<?php

namespace YBoard;

use Library\DbConnection;

abstract class Model
{
    protected $db;

    public function __construct(DbConnection $db = null)
    {
        if ($db) {
            $this->db = $db;
        }
    }
}
