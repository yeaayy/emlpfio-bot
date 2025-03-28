<?php

return [
    'up' => function (\PDO $db) {
        $db->query(
            "CREATE TABLE IF NOT EXISTS `posts` (
                `id` INTEGER PRIMARY KEY AUTOINCREMENT,
                `user_id` int NOT NULL,
                `frame_id` int NOT NULL,
                `fb_post` varchar(40) NOT NULL,
                UNIQUE (`user_id`,`frame_id`),
                FOREIGN KEY (`frame_id`) REFERENCES `frames` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE,
                FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE
              )"
        );
    },
    'down' => function (\PDO $db) {
        $db->query('DROP TABLE IF EXISTS `posts`');
    }
];
