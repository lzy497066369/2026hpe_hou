<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class GamePlayLog extends Model
{
    protected $fillable = [
        'user_id',
        'distance',
        'score',
        'duration',
        'played_at',
    ];

    protected function casts(): array
    {
        return [
            'played_at' => 'datetime',
        ];
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
