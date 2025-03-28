<?php

return [
    'up' => function (\PDO $db) {
        $db->query(
            'CREATE TABLE IF NOT EXISTS `users` (
                `id` INTEGER PRIMARY KEY AUTOINCREMENT,
                `tag` varchar(15) NOT NULL,
                `app_token` char(200) NOT NULL,
                `telegram_token` varchar(50) DEFAULT NULL,
                `page_id` varchar(20) DEFAULT NULL,
                `fb_token` varchar(255) DEFAULT NULL,
                `page_name` varchar(35) NOT NULL,
                `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
              )'
        );
    },
    'down' => function (\PDO $db) {
        $db->query('DROP TABLE IF EXISTS `users`');
    }
];
