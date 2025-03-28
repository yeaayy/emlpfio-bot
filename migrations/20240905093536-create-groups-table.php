<?php

return [
    'up' => function (\PDO $db) {
        $db->query(
            "CREATE TABLE IF NOT EXISTS `groups` (
                `id` INTEGER PRIMARY KEY AUTOINCREMENT,
                `show_id` int NOT NULL,
                `alias` varchar(10) NOT NULL,
                `name` varchar(40) NOT NULL,
                `fps` int NOT NULL DEFAULT '3',
                `gen` int NOT NULL DEFAULT '1',
                `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
                UNIQUE (`show_id`,`alias`),
                FOREIGN KEY (`show_id`) REFERENCES `shows` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE
              )"
        );
    },
    'down' => function (\PDO $db) {
        $db->query('DROP TABLE IF EXISTS `groups`');
    }
];
