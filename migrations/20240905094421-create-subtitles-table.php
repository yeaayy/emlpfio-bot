<?php

return [
    'up' => function (\PDO $db) {
        $db->query(
            "CREATE TABLE IF NOT EXISTS `subtitles` (
                `id` INTEGER PRIMARY KEY AUTOINCREMENT,
                `group_id` int NOT NULL,
                `start` float(8,3) NOT NULL,
                `end` float(8,3) NOT NULL,
                `text` text NOT NULL,
                FOREIGN KEY (`group_id`) REFERENCES `groups` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE
              )"
        );
    },
    'down' => function (\PDO $db) {
        $db->query('DROP TABLE IF EXISTS `subtitles`');
    }
];
