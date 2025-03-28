<?php

namespace App\Middleware;

use org\lumira\Errors\HttpError;
use org\lumira\fw\DB;
use org\lumira\fw\Request;

class Auth {
    function handle(Request $req, callable $next)
    {
        if (!key_exists('HTTP_X_AUTH', $_SERVER)) {
            throw new HttpError(401, 'Missing x-auth header');
        }
        $s = DB::prepare(
            'SELECT id, telegram_token, page_id, fb_token, page_name
            FROM users
            WHERE app_token = :app_token
            LIMIT 1'
        );
        $s->execute([
            'app_token' => $_SERVER['HTTP_X_AUTH'],
        ]);
        $row = $s->fetch();
        if (!$row) {
            throw new HttpError(401, 'Invalid access token');
        }
        $req['user_id'] = $row['id'];
        $req['telegram_token'] = $row['telegram_token'];
        $req['page_id'] = $row['page_id'];
        $req['fb_token'] = $row['fb_token'];
        $req['page_name'] = $row['page_name'];
        return $next();
    }
}
