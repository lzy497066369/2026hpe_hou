<?php

namespace App\Services\Upload;

use App\Models\UploadedFile;
use App\Models\User;
use Illuminate\Http\UploadedFile as HttpUploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class UploadService
{
    /**
     * @return array<string, string>
     */
    public function createPolicy(User $user, string $usageType): array
    {
        $disk = config('filesystems.default', 'local');
        $path = 'uploads/'.$usageType.'/'.now()->format('Ymd').'/'.Str::uuid();

        $file = UploadedFile::query()->create([
            'user_id' => $user->id,
            'disk' => $disk,
            'path' => $path,
            'url' => Storage::disk($disk)->url($path),
            'mime_type' => 'application/octet-stream',
            'size' => 0,
            'checksum' => null,
            'usage_type' => $usageType,
            'is_committed' => false,
        ]);

        return [
            'uploadUrl' => Storage::disk($disk)->url($path),
            'fileId' => (string) $file->id,
            'usageType' => $usageType,
            'objectKey' => $path,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function complete(User $user, string $fileId): array
    {
        $file = UploadedFile::query()
            ->where('id', $fileId)
            ->where('user_id', $user->id)
            ->firstOrFail();

        if (app()->environment('production') || $file->disk === 's3') {
            abort_if(!Storage::disk($file->disk)->exists($file->path), 422, '上传文件不存在');
        }

        if (Storage::disk($file->disk)->exists($file->path)) {
            $file->forceFill([
                'size' => Storage::disk($file->disk)->size($file->path),
                'mime_type' => Storage::disk($file->disk)->mimeType($file->path) ?? $file->mime_type,
                'url' => Storage::disk($file->disk)->url($file->path),
            ])->save();
        }

        return $this->formatFile($file);
    }

    /**
     * @return array<string, mixed>
     */
    public function storeLocal(User $user, string $usageType, HttpUploadedFile $upload): array
    {
        $path = $upload->store('uploads/'.$usageType.'/'.now()->format('Ymd'), 'public');

        $file = UploadedFile::query()->create([
            'user_id' => $user->id,
            'disk' => 'public',
            'path' => $path,
            'url' => Storage::disk('public')->url($path),
            'mime_type' => $upload->getMimeType() ?? 'application/octet-stream',
            'size' => $upload->getSize() ?: 0,
            'checksum' => hash_file('sha256', $upload->getRealPath()),
            'usage_type' => $usageType,
            'is_committed' => false,
        ]);

        return $this->formatFile($file);
    }

    /**
     * @return array<string, mixed>
     */
    private function formatFile(UploadedFile $file): array
    {
        return [
            'id' => (string) $file->id,
            'name' => basename($file->path),
            'url' => $file->url,
            'mimeType' => $file->mime_type,
            'size' => $file->size,
            'usageType' => $file->usage_type,
            'isCommitted' => $file->is_committed,
        ];
    }
}
