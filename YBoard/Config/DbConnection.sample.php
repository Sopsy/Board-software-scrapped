<?php

/// Set the PHP date offset to MySQL too.
$n = new \DateTime();
$h = $n->getOffset() / 3600;
$i = 60 * ($h - floor($h));
$offset = sprintf('%+d:%02d', $h, $i);
///

return [
    'pdoDsn' => 'mysql:host=xxx;dbname=xxx;charset=utf8mb4',
    'dbUsername' => 'xxx',
    'dbPassword' => 'xxx',
    'pdoParams' => [
        PDO::ATTR_EMULATE_PREPARES => false,
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::MYSQL_ATTR_INIT_COMMAND => "SET time_zone = '" . $offset . "'",
    ],
    'debug' => false,
];
