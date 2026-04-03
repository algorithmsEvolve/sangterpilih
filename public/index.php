<?php

ini_set('display_errors', '1');
ini_set('display_startup_errors', '1');
error_reporting(E_ALL);

use Illuminate\Foundation\Application;
use Illuminate\Http\Request;

define('LARAVEL_START', microtime(true));

// Determine if the application is in maintenance mode...
if (file_exists($maintenance = __DIR__.'/../storage/framework/maintenance.php')) {
    require $maintenance;
}

// Register the Composer autoloader...
require __DIR__.'/../vendor/autoload.php';

// Bootstrap Laravel and handle the request...
/** @var Application $app */
$app = require_once __DIR__.'/../bootstrap/app.php';

// Konfigurasi /tmp khusus untuk eksekusi serverless Vercel
if (isset($_ENV['VERCEL']) || getenv('VERCEL')) {
    $storagePath = '/tmp/storage';
    $directories = [
        '/framework/cache/data',
        '/framework/sessions',
        '/framework/views',
        '/logs'
    ];
    foreach ($directories as $dir) {
        if (!is_dir($storagePath . $dir)) {
            mkdir($storagePath . $dir, 0777, true);
        }
    }
    $app->useStoragePath($storagePath);
}

$app->handleRequest(Request::capture());
