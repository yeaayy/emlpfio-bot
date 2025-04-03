<?php

use App\Controllers\BackupController;
use App\Controllers\BestAlbumController;
use App\Controllers\FrameController;
use App\Controllers\GroupController;
use App\Controllers\PostController;
use App\Controllers\ReactController;
use App\Controllers\ShowController;
use App\Controllers\SubtitleController;
use App\Controllers\UserController;
use App\Middleware\Auth;
use App\Middleware\CheckBackupKey;
use org\lumira\fw\Route;

$route = new Route;

$route->get('route', function() use ($route) {
    $route->print();
});

$route->group('/backup', function (Route $route) {
    $route->middleware(CheckBackupKey::class);
    $route->post('/send', [BackupController::class, 'send']);
    $route->post('/last_id', [BackupController::class, 'getLastId']);
    $route->post('/receive', [BackupController::class, 'receive']);
});

$route->middleware(Auth::class);

$route->group('user', function (Route $route) {
    $route->get([UserController::class, 'get']);
    $route->post([UserController::class, 'update']);
    $route->get('check_token', [UserController::class, 'check_fb_token']);
});

$route->resources('/show', 'show', ShowController::class);
$route->resources('/show/:show/group', 'group', GroupController::class);
$route->get('/show/:show/react_stat', [ReactController::class, 'getShowReactStat']);
$route->group('/show/:show/group/:group', function (Route $route) {
    $route->get('frame_count', [GroupController::class, 'getFrameCount']);
    $route->post('fetch_reacts', [ReactController::class, 'fetchReacts']);
    $route->get('react_stat', [ReactController::class, 'getGroupReactStat']);

    $route->group('subtitle', function (Route $route) {
        $route->get([SubtitleController::class, 'getAll']);
        $route->get(':subs', [SubtitleController::class, 'get']);
    });
    $route->group('best', function (Route $route) {
        $route->get([BestAlbumController::class, 'getAlbum']);
        $route->post([BestAlbumController::class, 'createAlbum']);
        $route->post('populate', [BestAlbumController::class, 'populateAlbum']);
        $route->post('next', [BestAlbumController::class, 'postNext']);
        $route->post('clear', [BestAlbumController::class, 'clearAlbum']);
        $route->get(':frame', [BestAlbumController::class, 'getFrame']);
        $route->post(':frame/fix', [BestAlbumController::class, 'fixCaption']);
        $route->post(':frame/unpost', [BestAlbumController::class, 'unpostFrame']);
    });
    $route->group('frame', function (Route $route) {
        $route->group('/:frame/post', function (Route $route) {
            $route->get([PostController::class, 'getPost']);
            $route->post([PostController::class, 'createPost']);
            $route->delete([PostController::class, 'deletePost']);
        });
        $route->get('/:frame/subtitle', [PostController::class, 'getSubtitile']);
        $route->get(':frame/telegram_img', [FrameController::class, 'getTelegramImage']);
        $route->resources('/', 'frame', FrameController::class);
    });
});

return $route;
