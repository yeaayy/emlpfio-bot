<?php

return [
    'up' => function (\PDO $db) {
        $db->query(
            "CREATE TABLE IF NOT EXISTS `best_posts` (
                `id` INTEGER PRIMARY KEY AUTOINCREMENT,
                `album_id` int NOT NULL,
                `post_id` int NOT NULL,
                `reacts` int NOT NULL,
                `fb_post` varchar(40) DEFAULT NULL,
                FOREIGN KEY (`album_id`) REFERENCES `best_albums` (`id`)
              )"
        );
    },
    'down' => function (\PDO $db) {
        $db->query('DROP TABLE IF EXISTS `best_posts`');
    }
];
