<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Prize extends Model
{
    protected $fillable = [
        'name',
        'level',
        'stock',
        'status',
        'image_url',
    ];
}
