<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RegistrationProfile extends Model
{
    protected $fillable = [
        'user_id',
        'employee_no',
        'name',
        'department',
        'contact',
        'material_file_id',
        'audit_status',
        'audit_remark',
        'submitted_at',
        'reviewed_at',
    ];

    protected function casts(): array
    {
        return [
            'submitted_at' => 'datetime',
            'reviewed_at' => 'datetime',
        ];
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function materialFile()
    {
        return $this->belongsTo(UploadedFile::class, 'material_file_id');
    }
}
