<?php

namespace Library;

use PDO;

class DbConnection extends PDO
{
    private $config;

    public function __construct($config)
    {

        $this->config = $config;
        if (!$this->validateConfig()) {
            throw new \InvalidArgumentException('Malformed ' . __CLASS__ . ' configuration data');
        }

        $db = parent::__construct($this->config['pdoDsn'], $this->config['dbUsername'], $this->config['dbPassword'],
            $this->config['pdoParams']);

        if ($this->config['debug']) {
            $this->query("SET PROFILING = 1");
            $this->query("SET profiling_history_size = 100");
        }

        return $db;
    }

    private function validateConfig()
    {

        if (!is_array($this->config)) {
            return false;
        }
        if (!isset($this->config['debug'])) {
            $this->config['debug'] = false;
        }
        $this->config['debug'] = (bool)$this->config['debug'];

        if (empty($this->config['pdoDsn']) OR empty($this->config['dbUsername']) OR empty($this->config['dbPassword'])) {
            return false;
        }

        if (empty($this->config['pdoParams'])) {
            $this->config['pdoParams'] = [];
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
