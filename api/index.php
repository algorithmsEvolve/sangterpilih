<?php

require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';

// Force temp storage for Vercel's read-only filesystem
$storagePath = '/tmp/storage';
if (!is_dir($storagePath)) {
    mkdir($storagePath . '/framework/cache/data', 0777, true);
    mkdir($storagePath . '/framework/sessions', 0777, true);
    mkdir($storagePath . '/framework/views', 0777, true);
    mkdir($storagePath . '/logs', 0777, true);
}
$app->useStoragePath($storagePath);
// $app->useBootstrapPath('/tmp/bootstrap'); 

$app->handleRequest(Illuminate\Http\Request::capture());
