<?php

return [
    'up' => function (\PDO $db) {
        $db->query(
            "CREATE TABLE IF NOT EXISTS `frames` (
                `id` INTEGER PRIMARY KEY AUTOINCREMENT,
                `group_id` int NOT NULL,
                `frame_index` int NOT NULL,
                `file_id` varchar(90) NOT NULL,
                `path` varchar(30) DEFAULT NULL,
                `last_request` timestamp NULL DEFAULT NULL,
                `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
                UNIQUE (`group_id`,`frame_index`),
                FOREIGN KEY (`group_id`) REFERENCES `groups` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE
            )"
        );
    },
    'down' => function (\PDO $db) {
        $db->query('DROP TABLE IF EXISTS `frames`');
    }
];
