<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LotteryRecord extends Model
{
    protected $fillable = [
        'user_id',
        'prize_id',
        'work_id',
        'source_type',
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

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function work()
    {
        return $this->belongsTo(Work::class);
    }

    public function prizeClaim()
    {
        return $this->hasOne(PrizeClaim::class);
    }
}
