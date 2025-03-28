<?php

namespace App\Middleware;

use org\lumira\Errors\Forbiden;
use org\lumira\fw\cfg;
use org\lumira\fw\Request;
use org\lumira\fw\v;

class CheckBackupKey {
    function handle(Request $req, callable $next)
    {
        $v = $req->validate([
            'key' => v::required()->string()->min(1),
        ]);
        if ($v['key'] != cfg::backup_key()) {
            throw new Forbiden('Invalid backup key');
        }
        return $next();
    }
}
