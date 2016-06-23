<?php

return [
    'pdoDsn' => 'mysql:host=xxx;dbname=xxx;charset=utf8mb4',
    'dbUsername' => 'xxx',
    'dbPassword' => 'xxx',
    'pdoParams' => [
        PDO::ATTR_EMULATE_PREPARES => false,
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    ],
    'debug' => false,
];
