<?php
namespace YFW\Library;

use PDO;

class Database extends PDO
{
    protected $config;

    public function __construct(array $config)
    {
        // Validate config
        if (empty($config['pdoDsn']) || empty($config['dbUsername']) || empty($config['dbPassword'])) {
            throw new \InvalidArgumentException('Malformed ' . __CLASS__ . ' configuration data');
        }

        // Set defaults
        if (!isset($config['debug'])) {
            $config['debug'] = false;
        }
        $config['debug'] = (bool)$config['debug'];

        $config['pdoParams'] = [
            PDO::ATTR_EMULATE_PREPARES => false,
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_OBJ,
        ];

        $this->config = $config;

        // Connect
        parent::__construct($this->config['pdoDsn'], $this->config['dbUsername'], $this->config['dbPassword'],
            $this->config['pdoParams']);

        if ($this->config['debug']) {
            $this->query("SET PROFILING = 1");
            $this->query("SET profiling_history_size = 100");
        }
    }

    public function __destruct()
    {
        if ($this->config['debug']) {
            $q = $this->query("SHOW PROFILES");
            while ($row = $q->fetch(static::FETCH_ASSOC)) {
                error_log(__CLASS__ . ' debug:' . var_export($row, true), E_USER_NOTICE);
            }
        }
    }

    public function buildIn(array $array): string
    {
        $in = str_repeat('?,', count($array));
        $in = substr($in, 0, -1);

        return $in;
    }
}
