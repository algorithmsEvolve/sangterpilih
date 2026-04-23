<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\GameController;
use App\Http\Controllers\GameRedisController;

Route::get('/', function () {
    $spells = config('cards.spells', []);
    $traps = config('cards.traps', []);
    return view('welcome', compact('spells', 'traps'));
});

// === RUTING MYSQL/SUPABASE (DITIDURKAN) ===
// Route::post('/room/create', [GameController::class, 'createRoom']);
// Route::post('/room/join', [GameController::class, 'joinRoom']);
// Route::get('/room/{code}', [GameController::class, 'roomView']);
// Route::post('/room/{code}/start', [GameController::class, 'startGame']);
// Route::post('/room/{code}/roll', [GameController::class, 'rollDice']);
// Route::post('/room/{code}/end-turn', [GameController::class, 'endTurn']);
// Route::post('/room/{code}/shop/buy', [GameController::class, 'buyCard']);
// Route::post('/room/{code}/cards/use', [GameController::class, 'useCard']);
// Route::post('/room/{code}/cards/skip-trap', [GameController::class, 'skipTrap']);
// Route::post('/room/{code}/leave', [GameController::class, 'leaveRoom']);

// === RUTING REDIS/UPSTASH (AKTIF) ===
Route::post('/room/create', [GameRedisController::class, 'createRoom']);
Route::post('/room/join', [GameRedisController::class, 'joinRoom']);
Route::get('/room/{code}', [GameRedisController::class, 'roomView']);
Route::post('/room/{code}/start', [GameRedisController::class, 'startGame']);
Route::post('/room/{code}/roll', [GameRedisController::class, 'rollDice']);
Route::post('/room/{code}/end-turn', [GameRedisController::class, 'endTurn']);
Route::post('/room/{code}/shop/buy', [GameRedisController::class, 'buyCard']);
Route::post('/room/{code}/cards/use', [GameRedisController::class, 'useCard']);
Route::post('/room/{code}/cards/skip-trap', [GameRedisController::class, 'skipTrap']);
Route::post('/room/{code}/submit-loadout', [GameRedisController::class, 'submitLoadout']);
Route::post('/room/{code}/leave', [GameRedisController::class, 'leaveRoom']);
