<?php

namespace App\Controllers;

use App\Util;
use org\lumira\fw\DB;
use org\lumira\fw\Request;
use org\lumira\fw\v;

class UserController {

    function get(Request $req)
    {
        return [
            'telegram_token' => $req['telegram_token'],
            'fb_token' => $req['fb_token'],
            'page_id' => $req['page_id'],
            'page_name' => $req['page_name'],
        ];
    }

    function update(Request $req)
    {
        $v = $req->validate([
            'user_id' => v::required(),
            'new_telegram_token' => v::optional()->string()->range(1, 50),
            'new_fb_token' => v::optional()->string()->range(1, 255),
            'new_page_id' => v::optional()->string()->range(1, 20),
            'new_page_name' => v::optional()->string()->range(1, 35),
        ]);
        $s = DB::update(
            'UPDATE `users`
            SET %fields%, updated_at=@now
            WHERE id = :user_id',
            $v, [
                'telegram_token' => 'new_telegram_token',
                'fb_token' => 'new_fb_token',
                'page_id' => 'new_page_id',
                'page_name' => 'new_page_name',
            ]
        );
        if (!$s) return [ 'ok' => true ];

        $s->execute($v);
        return [ 'ok' => true ];
    }

    function check_fb_token(Request $req)
    {
        $v = $req->validate([
            'fb_token' => v::required(),
            'page_id' => v::required(),
        ]);
        return json_decode(Util::fetch("https://graph.facebook.com/v19.0/$v[page_id]", [
            'access_token' => $v['fb_token'],
        ]), true);
    }
}
