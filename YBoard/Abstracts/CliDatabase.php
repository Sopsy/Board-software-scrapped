<?php
namespace YBoard\Abstracts;

use YBoard\Library\Database;

abstract class CliDatabase
{
    protected $config;
    protected $db;
    
    public function __construct(array $config = null, Database $db = null)
    {
        // Load config
        if ($config === null) {
            $this->config = require(ROOT_PATH . '/YBoard/Config/YBoard.php');
        }

        // Get a database connection
        if ($db === null) {
            $this->db = new Database(require(ROOT_PATH . '/YBoard/Config/Database.php'));
        } else {
            $this->db = $db;
        }
    }
}
