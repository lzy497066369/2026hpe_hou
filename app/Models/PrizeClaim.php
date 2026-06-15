<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PrizeClaim extends Model
{
    protected $fillable = [
        'lottery_record_id',
        'user_id',
        'claim_type',
        'receiver_name',
        'receiver_phone',
        'receiver_address',
        'pickup_name',
        'pickup_phone',
        'pickup_employee_no',
        'pickup_remark',
        'claim_status',
    ];
}
