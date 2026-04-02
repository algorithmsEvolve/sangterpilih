<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\GameController;

Route::get('/', function () {
    return view('welcome');
});

Route::post('/room/create', [GameController::class, 'createRoom']);
Route::post('/room/join', [GameController::class, 'joinRoom']);
Route::get('/room/{code}', [GameController::class, 'roomView']);
Route::post('/room/{code}/start', [GameController::class, 'startGame']);
Route::post('/room/{code}/roll', [GameController::class, 'rollDice']);
Route::post('/room/{code}/leave', [GameController::class, 'leaveRoom']);
