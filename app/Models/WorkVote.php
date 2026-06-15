<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class WorkVote extends Model
{
    protected $fillable = [
        'work_id',
        'user_id',
        'vote_date',
        'source',
    ];

    protected function casts(): array
    {
        return [
            'vote_date' => 'date',
        ];
    }
}
