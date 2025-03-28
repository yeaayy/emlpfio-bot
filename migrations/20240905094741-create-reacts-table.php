<?php

return [
    'up' => function (\PDO $db) {
        $db->query(
            "CREATE TABLE IF NOT EXISTS `reacts` (
                `id` INTEGER PRIMARY KEY AUTOINCREMENT,
                `post_id` int NOT NULL,
                `react` char(1) NOT NULL,
                `count` int NOT NULL,
                UNIQUE (`post_id`,`react`),
                FOREIGN KEY (`post_id`) REFERENCES `posts` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE
              )"
        );
    },
    'down' => function (\PDO $db) {
        $db->query('DROP TABLE IF EXISTS `reacts`');
    }
];
