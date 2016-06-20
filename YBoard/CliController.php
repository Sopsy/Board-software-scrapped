<?php
namespace YBoard;

use YFW\Library\Database;

abstract class CliController
{
    protected $config;
    protected $db;

    public function __construct(array $config = null, Database $db = null)
    {
        // Load config
        if ($config === null) {
            $this->config = require(ROOT_PATH . '/YBoard/Config/App.php');
        } else {
            $this->config = $config;
        }

        // Get a database connection
        if ($db === null) {
            $this->db = new Database(require(ROOT_PATH . '/YBoard/Config/Database.php'));
        } else {
            $this->db = $db;
        }
    }
}
