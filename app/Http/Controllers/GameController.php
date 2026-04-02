<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Room;
use App\Models\Player;
use App\Events\PlayerJoined;
use App\Events\GameStarted;
use App\Events\DiceRolled;
use App\Events\TurnChanged;
use App\Events\GameOver;
use App\Events\RoomClosed;
use App\Events\PlayerLeft;

class GameController extends Controller
{
    public function createRoom(Request $request)
    {
        $request->validate([
            'host_name' => 'required|string|max:255',
            'code' => 'required|string|max:50|unique:rooms,code'
        ]);

        $room = Room::create([
            'code' => $request->code,
            'status' => 'waiting'
        ]);

        $player = $room->players()->create([
            'name' => $request->host_name,
            'is_host' => true
        ]);

        // store player id in session so we know who is who
        session(['player_id' => $player->id]);

        return redirect('/room/' . $room->code);
    }

    public function joinRoom(Request $request)
    {
        $request->validate([
            'player_name' => 'required|string|max:255',
            'code' => 'required|string|exists:rooms,code'
        ]);

        $room = Room::where('code', $request->code)->firstOrFail();

        if ($room->status !== 'waiting') {
            return back()->with('error', 'Room sudah mulai bermain!');
        }

        $player = $room->players()->create([
            'name' => $request->player_name,
            'is_host' => false
        ]);

        session(['player_id' => $player->id]);

        broadcast(new PlayerJoined($roomCode = $room->code, $player->toArray()));

        return redirect('/room/' . $room->code);
    }

    public function roomView($code)
    {
        $room = Room::where('code', $code)->with('players')->firstOrFail();
        $currentPlayerId = session('player_id');
        
        if (!$currentPlayerId) {
            return redirect('/')->with('error', 'Silakan join/create room terlebih dahulu.');
        }
        
        $currentPlayer = Player::find($currentPlayerId);

        return view('room', compact('room', 'currentPlayer'));
    }

    public function startGame($code)
    {
        $room = Room::where('code', $code)->firstOrFail();
        $currentPlayerId = session('player_id');
        $currentPlayer = Player::find($currentPlayerId);

        if (!$currentPlayer || !$currentPlayer->is_host) {
            return response()->json(['error' => 'Not host'], 403);
        }

        $players = $room->players()->orderBy('id')->get();
        if ($players->count() < 2) {
            return response()->json(['error' => 'Butuh minimal 2 pemain!'], 400);
        }

        $room->status = 'playing';
        $room->current_turn_player_id = $players->first()->id;
        $room->save();

        broadcast(new GameStarted($room->code, $room->current_turn_player_id));

        return response()->json(['success' => true]);
    }

    public function rollDice(Request $request, $code)
    {
        $room = Room::where('code', $code)->firstOrFail();
        $currentPlayerId = session('player_id');
        $player = Player::find($currentPlayerId);

        if ($room->status !== 'playing') {
            return response()->json(['error' => 'Game is not in playing state'], 400);
        }

        if ($room->current_turn_player_id !== $player->id) {
            return response()->json(['error' => 'Bukan giliranmu!'], 400);
        }

        if ($player->has_rolled) {
            return response()->json(['error' => 'Kamu sudah melempar dadu!'], 400);
        }

        // Roll the dice
        $diceResult = rand(1, 6);
        $player->score += $diceResult;
        $player->has_rolled = true;
        $player->save();

        broadcast(new DiceRolled($room->code, $player->id, $diceResult, $player->score));

        // Check if everyone rolled
        $unrolledPlayer = $room->players()->where('has_rolled', false)->orderBy('id')->first();

        if ($unrolledPlayer) {
            $room->current_turn_player_id = $unrolledPlayer->id;
            $room->save();
            
            // Turn Changed
            broadcast(new TurnChanged($room->code, $unrolledPlayer->id));
        } else {
            // Game Over
            $room->status = 'finished';
            $room->save();

            $leaderboard = $room->players()->orderByDesc('score')->get()->toArray();
            broadcast(new GameOver($room->code, $leaderboard));
        }

        return response()->json([
            'success' => true,
            'diceResult' => $diceResult,
            'score' => $player->score,
            'nextTurn' => $unrolledPlayer ? $unrolledPlayer->id : null,
            'gameOver' => !$unrolledPlayer
        ]);
    }

    public function leaveRoom(Request $request, $code)
    {
        $currentPlayerId = session('player_id');
        if (!$currentPlayerId) {
            return response()->json(['success' => false]);
        }

        $player = Player::find($currentPlayerId);
        if ($player && $player->is_host) {
            $room = Room::where('code', $code)->first();
            if ($room) {
                broadcast(new RoomClosed($room->code));
                $room->delete();
            }
        } else if ($player) {
            $playerId = $player->id;
            $player->delete();
            broadcast(new PlayerLeft($code, $playerId));
        }

        return response()->json(['success' => true]);
    }
}
