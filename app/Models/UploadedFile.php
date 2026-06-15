<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class UploadedFile extends Model
{
    protected $fillable = [
        'user_id',
        'disk',
        'path',
        'url',
        'mime_type',
        'size',
        'checksum',
        'usage_type',
        'is_committed',
    ];

    protected function casts(): array
    {
        return [
            'is_committed' => 'boolean',
        ];
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
