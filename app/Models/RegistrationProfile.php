<?php

namespace App\Models;

use App\Support\AdminDisplay;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;

class RegistrationProfile extends Model
{
    protected $fillable = [
        'user_id',
        'employee_no',
        'name',
        'department',
        'contact',
        'material_file_id',
        'material_file_ids',
        'audit_status',
        'audit_remark',
        'submitted_at',
        'reviewed_at',
    ];

    protected function casts(): array
    {
        return [
            'material_file_ids' => 'array',
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

    public function materialFilesForDisplay(): Collection
    {
        $fileIds = $this->material_file_ids ?: array_filter([$this->material_file_id]);

        if ($fileIds === []) {
            return collect();
        }

        $orderedIds = array_map('strval', $fileIds);

        return UploadedFile::query()
            ->whereIn('id', $fileIds)
            ->get()
            ->sortBy(static fn (UploadedFile $file): int => array_search((string) $file->id, $orderedIds, true))
            ->values();
    }

    /**
     * @return list<string>
     */
    public function materialImageUrls(): array
    {
        return $this->materialFilesForDisplay()
            ->filter(static fn (UploadedFile $file): bool => str_starts_with((string) $file->mime_type, 'image/') && filled($file->url))
            ->map(static fn (UploadedFile $file): ?string => AdminDisplay::fileUrl($file))
            ->filter()
            ->values()
            ->all();
    }
}
