<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LotteryRecord extends Model
{
    protected $fillable = [
        'user_id',
        'prize_id',
        'result_status',
        'drawn_at',
    ];

    protected function casts(): array
    {
        return [
            'drawn_at' => 'datetime',
        ];
    }

    public function prize()
    {
        return $this->belongsTo(Prize::class);
    }

    public function prizeClaim()
    {
        return $this->hasOne(PrizeClaim::class);
    }
}
