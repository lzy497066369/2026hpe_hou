<?php

namespace App\Services\Registration;

use App\Enums\RegistrationAuditStatus;
use App\Enums\UploadUsageType;
use App\Models\QuotaApplication;
use App\Models\RegistrationProfile;
use App\Models\UploadedFile;
use App\Models\User;

class RegistrationService
{
    /**
     * @return array<string, mixed>
     */
    public function profile(User $user): array
    {
        $profile = $user->registrationProfile;

        if ($profile === null) {
            return [
                'id' => '',
                'userId' => (string) $user->id,
                'employeeNo' => $user->employee_no,
                'email' => $user->email,
                'name' => $user->name,
                'department' => $this->userDepartment($user),
                'contact' => $user->phone ?? '',
            'materialFileId' => null,
            'materialFileIds' => [],
            'materialFiles' => [],
            'auditStatus' => RegistrationAuditStatus::Draft->value,
            'auditRemark' => null,
                'submittedAt' => null,
                'reviewedAt' => null,
            ];
        }

        return $this->formatProfile($profile);
    }

    /**
     * @return array<string, mixed>
     */
    public function status(User $user): array
    {
        $profile = $user->registrationProfile;

        if ($profile === null) {
            return [
                'auditStatus' => RegistrationAuditStatus::Draft->value,
                'auditRemark' => null,
                'submittedAt' => null,
                'reviewedAt' => null,
            ];
        }

        return [
            'auditStatus' => $profile->audit_status,
            'auditRemark' => $profile->audit_remark,
            'submittedAt' => $profile->submitted_at?->toISOString(),
            'reviewedAt' => $profile->reviewed_at?->toISOString(),
        ];
    }

    /**
     * @param array<string, mixed> $payload
     * @return array<string, mixed>
     */
    public function submit(User $user, array $payload): array
    {
        if (($payload['applicationType'] ?? 'registration') === 'extra_quota') {
            return $this->submitExtraQuotaApplication($user, $payload);
        }

        $existing = $user->registrationProfile;

        abort_if(
            $existing !== null && $existing->audit_status === RegistrationAuditStatus::UnderReview->value,
            422,
            '当前审核状态不允许提交'
        );

        $materialFileIds = $this->normalizeMaterialFileIds($payload);
        if ($materialFileIds !== []) {
            $matchedFileCount = UploadedFile::query()
                ->whereIn('id', $materialFileIds)
                ->where('user_id', $user->id)
                ->where('usage_type', UploadUsageType::RegistrationMaterial->value)
                ->count();

            abort_if($matchedFileCount !== count($materialFileIds), 422, '材料文件不存在或不属于当前用户');
        }

        $profile = RegistrationProfile::query()->updateOrCreate(
            ['user_id' => $user->id],
            [
                'employee_no' => $user->employee_no,
                'name' => $user->name,
                'department' => $this->userDepartment($user) ?: ($existing->department ?? ''),
                'contact' => $user->phone ?? ($existing->contact ?? ''),
                'material_file_id' => $materialFileIds[0] ?? $existing?->material_file_id,
                'material_file_ids' => $materialFileIds ?: ($existing?->material_file_ids ?? null),
                'audit_status' => RegistrationAuditStatus::Submitted->value,
                'submitted_at' => now(),
            ]
        );

        $committedFileIds = $profile->material_file_ids ?: array_filter([$profile->material_file_id]);
        if ($committedFileIds !== []) {
            UploadedFile::query()
                ->whereIn('id', $committedFileIds)
                ->where('user_id', $user->id)
                ->update(['is_committed' => true]);
        }

        return $this->formatProfile($profile->fresh());
    }

    /**
     * @param array<string, mixed> $payload
     * @return array<string, mixed>
     */
    private function submitExtraQuotaApplication(User $user, array $payload): array
    {
        $materialFileIds = $this->normalizeMaterialFileIds($payload);

        $matchedFileCount = UploadedFile::query()
            ->whereIn('id', $materialFileIds)
            ->where('user_id', $user->id)
            ->where('usage_type', UploadUsageType::RegistrationMaterial->value)
            ->count();

        abort_if($materialFileIds === [] || $matchedFileCount !== count($materialFileIds), 422, '材料文件不存在或不属于当前用户');

        abort_if(
            $this->findDuplicateQuotaApplication($user, $materialFileIds) !== null,
            422,
            '重复提交，请更换材料后再提交'
        );

        $application = QuotaApplication::query()->create([
            'user_id' => $user->id,
            'employee_no' => $user->employee_no,
            'material_file_ids' => $materialFileIds,
            'audit_status' => RegistrationAuditStatus::Submitted->value,
            'submitted_at' => now(),
        ]);

        UploadedFile::query()
            ->whereIn('id', $materialFileIds)
            ->where('user_id', $user->id)
            ->update(['is_committed' => true]);

        return $this->formatQuotaApplication($application->fresh());
    }

    /**
     * @param list<string> $materialFileIds
     */
    private function findDuplicateQuotaApplication(User $user, array $materialFileIds): ?QuotaApplication
    {
        $currentSignature = $this->materialChecksumSignature($materialFileIds);
        if ($currentSignature === []) {
            return null;
        }

        $applications = QuotaApplication::query()
            ->where('user_id', $user->id)
            ->latest('id')
            ->get();

        foreach ($applications as $application) {
            if ($this->materialChecksumSignature($application->material_file_ids ?: []) === $currentSignature) {
                return $application;
            }
        }

        return null;
    }

    /**
     * @param array<int, int|string> $materialFileIds
     * @return list<string>
     */
    private function materialChecksumSignature(array $materialFileIds): array
    {
        if ($materialFileIds === []) {
            return [];
        }

        $checksums = UploadedFile::query()
            ->whereIn('id', $materialFileIds)
            ->pluck('checksum')
            ->filter()
            ->map(static fn (string $checksum): string => trim($checksum))
            ->filter(static fn (string $checksum): bool => $checksum !== '')
            ->sort()
            ->values()
            ->all();

        return count($checksums) === count($materialFileIds) ? $checksums : [];
    }

    /**
     * @return array<string, mixed>
     */
    private function formatProfile(RegistrationProfile $profile): array
    {
        $materialFiles = $this->formatMaterialFiles($profile);

        return [
            'id' => (string) $profile->id,
            'userId' => (string) $profile->user_id,
            'employeeNo' => $profile->employee_no,
            'email' => $profile->user?->email,
            'name' => $profile->name,
            'department' => $profile->department,
            'contact' => $profile->contact,
            'materialFileId' => $profile->material_file_id === null ? null : (string) $profile->material_file_id,
            'materialFileIds' => array_column($materialFiles, 'id'),
            'materialFiles' => $materialFiles,
            'auditStatus' => $profile->audit_status,
            'auditRemark' => $profile->audit_remark,
            'submittedAt' => $profile->submitted_at?->toISOString(),
            'reviewedAt' => $profile->reviewed_at?->toISOString(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function formatQuotaApplication(QuotaApplication $application): array
    {
        $materialFiles = $application->materialFilesForDisplay()
            ->map(static fn (UploadedFile $file): array => [
                'id' => (string) $file->id,
                'name' => basename($file->path),
                'url' => $file->url,
                'mimeType' => $file->mime_type,
                'size' => $file->size,
            ])
            ->all();

        return [
            'id' => (string) $application->id,
            'userId' => (string) $application->user_id,
            'employeeNo' => $application->employee_no,
            'email' => $application->user?->email,
            'name' => $application->user?->name ?? '',
            'department' => $this->userDepartment($application->user) ?: '',
            'contact' => $application->user?->phone ?? '',
            'materialFileId' => $application->material_file_ids[0] ?? null,
            'materialFileIds' => array_column($materialFiles, 'id'),
            'materialFiles' => $materialFiles,
            'auditStatus' => $application->audit_status,
            'auditRemark' => $application->audit_remark,
            'submittedAt' => $application->submitted_at?->toISOString(),
            'reviewedAt' => $application->reviewed_at?->toISOString(),
        ];
    }

    /**
     * @param array<string, mixed> $payload
     * @return list<string>
     */
    private function normalizeMaterialFileIds(array $payload): array
    {
        $fileIds = $payload['materialFileIds'] ?? [];

        if (!is_array($fileIds)) {
            $fileIds = [];
        }

        if (($payload['materialFileId'] ?? null) !== null) {
            array_unshift($fileIds, $payload['materialFileId']);
        }

        return array_values(array_unique(array_filter(
            array_map(static fn ($fileId): string => (string) $fileId, $fileIds),
            static fn (string $fileId): bool => $fileId !== ''
        )));
    }

    private function userDepartment(User $user): string
    {
        $department = $user->getAttribute('department');

        return is_string($department) ? trim($department) : '';
    }

    /**
     * @return list<array{id: string, name: string, url: string, mimeType: string, size: int}>
     */
    private function formatMaterialFiles(RegistrationProfile $profile): array
    {
        $fileIds = $profile->material_file_ids ?: array_filter([$profile->material_file_id]);
        if ($fileIds === []) {
            return [];
        }

        return UploadedFile::query()
            ->whereIn('id', $fileIds)
            ->where('user_id', $profile->user_id)
            ->get()
            ->sortBy(static fn (UploadedFile $file): int => array_search((string) $file->id, array_map('strval', $fileIds), true))
            ->values()
            ->map(static fn (UploadedFile $file): array => [
                'id' => (string) $file->id,
                'name' => basename($file->path),
                'url' => $file->url,
                'mimeType' => $file->mime_type,
                'size' => $file->size,
            ])
            ->all();
    }
}
