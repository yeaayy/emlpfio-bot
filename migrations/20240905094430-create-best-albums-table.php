<?php

return [
    'up' => function (\PDO $db) {
        $db->query(
            "CREATE TABLE IF NOT EXISTS `best_albums` (
                `id` INTEGER PRIMARY KEY AUTOINCREMENT,
                `user_id` int NOT NULL,
                `fb_album` varchar(20) NOT NULL,
                `group_id` int NOT NULL,
                `token` char(8) UNIQUE NOT NULL,
                `alias` varchar(10) UNIQUE NOT NULL,
                FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE,
                FOREIGN KEY (`group_id`) REFERENCES `groups` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE
              )"
        );
    },
    'down' => function (\PDO $db) {
        $db->query('DROP TABLE IF EXISTS `best_albums`');
    }
];
