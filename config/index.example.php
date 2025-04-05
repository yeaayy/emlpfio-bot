<?php
return [
    'log_path' => __DIR__ . "/../log/error.log",
    'backup' => [
        'key' => '',
        'limit' => 5000,
    ],
    'db' => [
        'driver' => 'mysql',
        'host' => 'localhost',
        'dbname' => 'database',
        'username' => '',
        'password' => '',

        // 'driver' => 'sqlite',
        // 'path' => __DIR__ . '/../db/main.db',
    ],
];
