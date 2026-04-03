<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Player extends Model
{
    protected $guarded = [];
    protected $casts = [
        'inventory' => 'array',
    ];

    public function room()
    {
        return $this->belongsTo(Room::class);
    }
}
