<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Room extends Model
{
    protected $guarded = [];
    protected $casts = [
        'active_turn_snapshot' => 'array',
        'pending_trap_confirmations' => 'array',
    ];

    public function players()
    {
        return $this->hasMany(Player::class);
    }
}
