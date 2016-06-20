<?php
namespace YFW;

use YFW\Library\Database;

abstract class Model
{
    protected $db;

    public function __construct(Database $db)
    {
        $this->db = $db;
    }
}
