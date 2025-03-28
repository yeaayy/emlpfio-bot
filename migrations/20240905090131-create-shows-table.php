<?php

return [
    'up' => function (\PDO $db) {
        $db->query(
            'CREATE TABLE IF NOT EXISTS `shows` (
                `id` INTEGER PRIMARY KEY AUTOINCREMENT,
                `name` varchar(40) UNIQUE NOT NULL,
                `alias` varchar(10) UNIQUE NOT NULL,
                `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
            )'
        );
    },
    'down' => function (\PDO $db) {
        $db->query('DROP TABLE IF EXISTS `shows`');
    }
];
