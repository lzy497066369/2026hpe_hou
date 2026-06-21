<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LotteryQualification extends Model
{
    protected $fillable = [
        'user_id',
        'source_type',
        'qualified',
        'chance_count',
        'used_count',
    ];

    protected function casts(): array
    {
        return [
            'qualified' => 'boolean',
        ];
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
