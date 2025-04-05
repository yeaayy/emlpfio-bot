<?php

namespace App\Middleware;

use org\lumira\Errors\Forbiden;
use org\lumira\Errors\HttpError;
use org\lumira\fw\cfg;
use org\lumira\fw\Request;

class CheckBackupKey {
    function handle(Request $req, callable $next)
    {
        if (!key_exists('HTTP_X_KEY', $_SERVER)) {
            throw new HttpError(401, 'Missing X-Key header');
        }
        if ($_SERVER['HTTP_X_KEY'] != cfg::backup()->key) {
            throw new Forbiden('Invalid backup key');
        }
        return $next();
    }
}
