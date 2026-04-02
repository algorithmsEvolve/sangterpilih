<?php

require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';

// Force temp storage for Vercel's read-only filesystem
$app->useStoragePath('/tmp/storage');
// $app->useBootstrapPath('/tmp/bootstrap'); 

$app->handleRequest(Illuminate\Http\Request::capture());
