<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ActivitySetting extends Model
{
    protected $fillable = [
        'key',
        'label',
        'type',
        'value',
        'description',
    ];
}
