<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Work extends Model
{
    protected $fillable = [
        'user_id',
        'type',
        'group',
        'title',
        'description',
        'cover_file_id',
        'content_file_id',
        'tool_name',
        'prompt_text',
        'audit_status',
        'publish_status',
        'vote_count',
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

    public function coverFile()
    {
        return $this->belongsTo(UploadedFile::class, 'cover_file_id');
    }

    public function contentFile()
    {
        return $this->belongsTo(UploadedFile::class, 'content_file_id');
    }

    public function votes()
    {
        return $this->hasMany(WorkVote::class);
    }
}
