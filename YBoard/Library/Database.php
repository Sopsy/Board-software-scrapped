<?php

namespace YBoard\Library;

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

        if (empty($config['pdoParams'])) {
            $config['pdoParams'] = [];
        }

        $this->config = $config;

        // Connect
        parent::__construct($this->config['pdoDsn'], $this->config['dbUsername'],
            $this->config['dbPassword'], $this->config['pdoParams']);

        if ($this->config['debug']) {
            $this->query("SET PROFILING = 1");
            $this->query("SET profiling_history_size = 100");
        }

        return true;
    }

    public function __destruct()
    {
        if ($this->config['debug']) {
            $q = $this->query("SHOW PROFILES");
            while ($row = $q->fetch(static::FETCH_ASSOC)) {
                error_log('DbConnection debug:' . var_export($row, true), E_USER_NOTICE);
            }
        }
    }

    public function buildSetQuery(array $cols, array $vals)
    {
        $query = '';
        $i = 0;
        foreach ($cols AS $col) {
            $query .= "`" . str_replace('`', '``',
                    $col) . "` = " . ($vals[$i] !== null ? $this->quote($vals[$i]) : 'NULL') . ", ";
            ++$i;
        }

        return mb_substr($query, 0, -2);
    }

    public function buildInsertQuery(array $cols, array $vals)
    {
        $query = ['', ''];
        $i = 0;
        foreach ($cols AS $col) {
            $query[0] .= "`" . str_replace('`', '``', $col) . "`, ";
            $query[1] .= $this->quote($vals[$i]) . ", ";
            ++$i;
        }

        foreach ($query AS &$q) {
            $q = mb_substr($q, 0, -2);
        }

        return $query;
    }
}
