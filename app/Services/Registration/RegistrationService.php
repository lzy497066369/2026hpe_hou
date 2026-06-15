<?php

namespace App\Services\Registration;

use App\Enums\RegistrationAuditStatus;
use App\Enums\UploadUsageType;
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
                'name' => $user->name,
                'department' => '',
                'contact' => $user->phone ?? '',
                'materialFileId' => null,
                'auditStatus' => RegistrationAuditStatus::Draft->value,
                'auditRemark' => null,
                'submittedAt' => null,
                'reviewedAt' => null,
            ];
        }

        return $this->formatProfile($profile);
    }

    /**
     * @param array<string, mixed> $payload
     * @return array<string, mixed>
     */
    public function submit(User $user, array $payload): array
    {
        $existing = $user->registrationProfile;

        abort_if(
            $existing !== null && $existing->audit_status === RegistrationAuditStatus::UnderReview->value,
            422,
            '当前审核状态不允许提交'
        );

        if (($payload['materialFileId'] ?? null) !== null) {
            $file = UploadedFile::query()
                ->where('id', $payload['materialFileId'])
                ->where('user_id', $user->id)
                ->where('usage_type', UploadUsageType::RegistrationMaterial->value)
                ->first();

            abort_if($file === null, 422, '材料文件不存在或不属于当前用户');
        }

        $profile = RegistrationProfile::query()->updateOrCreate(
            ['user_id' => $user->id],
            [
                'employee_no' => $payload['employeeNo'] ?? $user->employee_no,
                'name' => $payload['name'] ?? $user->name,
                'department' => $payload['department'] ?? ($existing->department ?? ''),
                'contact' => $payload['contact'] ?? ($user->phone ?? ''),
                'material_file_id' => $payload['materialFileId'] ?? $existing?->material_file_id,
                'audit_status' => RegistrationAuditStatus::Submitted->value,
                'submitted_at' => now(),
            ]
        );

        if ($profile->material_file_id !== null) {
            UploadedFile::query()
                ->where('id', $profile->material_file_id)
                ->where('user_id', $user->id)
                ->update(['is_committed' => true]);
        }

        return $this->formatProfile($profile->fresh());
    }

    /**
     * @return array<string, mixed>
     */
    private function formatProfile(RegistrationProfile $profile): array
    {
        return [
            'id' => (string) $profile->id,
            'userId' => (string) $profile->user_id,
            'employeeNo' => $profile->employee_no,
            'name' => $profile->name,
            'department' => $profile->department,
            'contact' => $profile->contact,
            'materialFileId' => $profile->material_file_id === null ? null : (string) $profile->material_file_id,
            'auditStatus' => $profile->audit_status,
            'auditRemark' => $profile->audit_remark,
            'submittedAt' => $profile->submitted_at?->toISOString(),
            'reviewedAt' => $profile->reviewed_at?->toISOString(),
        ];
    }
}
