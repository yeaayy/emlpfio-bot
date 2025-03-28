<?php

require_once __DIR__ . '/../vendor/autoload.php';

use org\lumira\Errors\HttpError;
use org\lumira\fw\cfg;
use org\lumira\fw\Request;
use org\lumira\Validation\ValidationError;

cfg::load(__DIR__ . '/../config/index.php');

ini_set('display_errors', 0);
ini_set("log_errors", 1);
ini_set("error_log", cfg::log_path());

$route = require __DIR__.'/../app/routes.php';

try {
    $paths = explode('/', preg_replace("/\?.+$/", "", $_SERVER['REQUEST_URI']));
    $paths = array_filter($paths, function($value) {
        return $value !== '';
    });
    $result = $route->handle($paths, strtoupper($_SERVER['REQUEST_METHOD']), Request::capture());
    switch (gettype($result)) {
    case 'string':
        printf("%s\n", $result);
        break;
    case 'array':
        header("Content-Type: application/json");
        printf("%s\n", json_encode($result));
        break;
    }
    exit;
} catch(ValidationError $e) {
    http_response_code(400);
    echo json_encode([
        'error' => $e->errors,
    ]);
} catch(HttpError $e) {
    http_response_code($e->getCode());
    echo json_encode([
        'error' => $e->getMessage(),
    ]);
}
header("Content-Type: application/json");
echo "\n";
