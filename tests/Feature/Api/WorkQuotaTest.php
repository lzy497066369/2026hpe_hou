<?php

namespace Tests\Feature\Api;

use App\Enums\RegistrationAuditStatus;
use App\Enums\UploadUsageType;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class WorkQuotaTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_has_one_default_work_quota(): void
    {
        [$user, $headers] = $this->createLoggedInUser();

        $this->submitWork($headers, $user, 'First Work')->assertOk();

        $this->submitWork($headers, $user, 'Second Work')
            ->assertStatus(422)
            ->assertJsonPath('message', '当前可上传作品名额已用完，请申请更多名额');
    }

    public function test_submitted_extra_quota_application_does_not_increase_work_quota_until_approved(): void
    {
        [$user, $headers] = $this->createLoggedInUser();

        $this->submitWork($headers, $user, 'First Work')->assertOk();
        $this->submitExtraQuotaApplication($headers, $user)->assertOk();

        $this->submitWork($headers, $user, 'Second Work')
            ->assertStatus(422)
            ->assertJsonPath('message', '当前可上传作品名额已用完，请申请更多名额');
    }

    public function test_extra_quota_status_returns_error_when_no_application_exists(): void
    {
        [, $headers] = $this->createLoggedInUser();

        $this->withHeaders($headers)
            ->getJson('/api/v1/registration/status?applicationType=extra_quota')
            ->assertStatus(422)
            ->assertJsonPath('message', '暂未提交材料审核');
    }

    public function test_extra_quota_status_returns_latest_application_status(): void
    {
        [$user, $headers] = $this->createLoggedInUser();

        $firstApplicationId = $this->submitExtraQuotaApplication($headers, $user, [
            $this->createRegistrationMaterialFile($user, 'status-material-a-1'),
            $this->createRegistrationMaterialFile($user, 'status-material-a-2'),
        ])->json('data.id');
        \App\Models\QuotaApplication::query()
            ->whereKey($firstApplicationId)
            ->update([
                'audit_status' => RegistrationAuditStatus::Approved->value,
                'reviewed_at' => now(),
            ]);

        $this->submitExtraQuotaApplication($headers, $user, [
            $this->createRegistrationMaterialFile($user, 'status-material-b-1'),
            $this->createRegistrationMaterialFile($user, 'status-material-b-2'),
        ])->assertOk();

        $this->withHeaders($headers)
            ->getJson('/api/v1/registration/status?applicationType=extra_quota')
            ->assertOk()
            ->assertJsonPath('data.auditStatus', RegistrationAuditStatus::Submitted->value);
    }

    public function test_extra_quota_application_with_same_material_images_is_rejected_as_duplicate(): void
    {
        [$user, $headers] = $this->createLoggedInUser();
        $materialOneChecksum = uniqid('same_material_one_', true);
        $materialTwoChecksum = uniqid('same_material_two_', true);

        $firstApplicationId = $this->submitExtraQuotaApplication($headers, $user, [
            $this->createRegistrationMaterialFile($user, $materialOneChecksum),
            $this->createRegistrationMaterialFile($user, $materialTwoChecksum),
        ])->json('data.id');
        $secondApplicationId = $this->submitExtraQuotaApplication($headers, $user, [
            $this->createRegistrationMaterialFile($user, $materialOneChecksum),
            $this->createRegistrationMaterialFile($user, $materialTwoChecksum),
        ]);

        $secondApplicationId
            ->assertStatus(422)
            ->assertJsonPath('message', '重复提交，请更换材料后再提交');
        $this->assertDatabaseCount('quota_applications', 1);
        $this->assertDatabaseHas('quota_applications', [
            'id' => $firstApplicationId,
            'audit_status' => RegistrationAuditStatus::Submitted->value,
        ]);
    }

    public function test_extra_quota_application_with_different_material_images_creates_new_record(): void
    {
        [$user, $headers] = $this->createLoggedInUser();

        $firstApplicationId = $this->submitExtraQuotaApplication($headers, $user, [
            $this->createRegistrationMaterialFile($user, 'material-a-1'),
            $this->createRegistrationMaterialFile($user, 'material-a-2'),
        ])->json('data.id');
        $secondApplicationId = $this->submitExtraQuotaApplication($headers, $user, [
            $this->createRegistrationMaterialFile($user, 'material-b-1'),
            $this->createRegistrationMaterialFile($user, 'material-b-2'),
        ])->json('data.id');

        $this->assertNotSame($firstApplicationId, $secondApplicationId);
        $this->assertDatabaseCount('quota_applications', 2);
        $this->assertDatabaseHas('quota_applications', [
            'id' => $firstApplicationId,
            'audit_status' => RegistrationAuditStatus::Submitted->value,
        ]);
        $this->assertDatabaseHas('quota_applications', [
            'id' => $secondApplicationId,
            'audit_status' => RegistrationAuditStatus::Submitted->value,
        ]);
    }

    public function test_each_approved_extra_quota_application_allows_one_more_work(): void
    {
        [$user, $headers] = $this->createLoggedInUser();

        $this->submitWork($headers, $user, 'First Work')->assertOk();
        $firstApplicationId = $this->submitExtraQuotaApplication($headers, $user)->json('data.id');

        $this->assertNotEmpty($firstApplicationId);
        $this->assertDatabaseHas('quota_applications', [
            'id' => $firstApplicationId,
            'audit_status' => RegistrationAuditStatus::Submitted->value,
        ]);

        \App\Models\QuotaApplication::query()
            ->whereKey($firstApplicationId)
            ->update([
                'audit_status' => RegistrationAuditStatus::Approved->value,
                'reviewed_at' => now(),
            ]);

        $this->submitWork($headers, $user, 'Second Work')->assertOk();

        $this->submitWork($headers, $user, 'Third Work')
            ->assertStatus(422)
            ->assertJsonPath('message', '当前可上传作品名额已用完，请申请更多名额');

        $secondApplicationId = $this->submitExtraQuotaApplication($headers, $user)->json('data.id');
        \App\Models\QuotaApplication::query()
            ->whereKey($secondApplicationId)
            ->update([
                'audit_status' => RegistrationAuditStatus::Approved->value,
                'reviewed_at' => now(),
            ]);

        $this->submitWork($headers, $user, 'Third Work')->assertOk();
    }

    /**
     * @return array{0: User, 1: array<string, string>}
     */
    private function createLoggedInUser(): array
    {
        $user = User::query()->create([
            'name' => 'Quota User',
            'email' => 'quota@example.com',
            'employee_no' => 'Q0001',
            'nickname' => 'quota',
            'password' => 'unused',
            'status' => 'active',
        ]);

        $token = $this->postJson('/api/v1/auth/login', [
            'employeeNo' => $user->employee_no,
            'email' => $user->email,
            'nickname' => $user->nickname,
        ])->json('data.token');

        return [$user, ['Authorization' => 'Bearer '.$token]];
    }

    private function submitWork(array $headers, User $user, string $title): \Illuminate\Testing\TestResponse
    {
        return $this->withHeaders($headers)->postJson('/api/v1/works/submit', [
            'type' => 'traditional',
            'group' => 'employee',
            'title' => $title,
            'description' => $title.' description',
            'contentFileId' => $this->createWorkContentFile($user),
        ]);
    }

    private function submitExtraQuotaApplication(array $headers, User $user, ?array $materialFileIds = null): \Illuminate\Testing\TestResponse
    {
        return $this->withHeaders($headers)->postJson('/api/v1/registration/submit', [
            'applicationType' => 'extra_quota',
            'materialFileIds' => $materialFileIds ?? [
                $this->createRegistrationMaterialFile($user),
                $this->createRegistrationMaterialFile($user),
            ],
        ]);
    }

    private function createWorkContentFile(User $user): int
    {
        return (int) $user->uploadedFiles()->create([
            'disk' => 'local',
            'path' => 'uploads/work-content/'.uniqid('work_', true).'.png',
            'url' => 'https://example.com/work.png',
            'mime_type' => 'image/png',
            'size' => 1024,
            'checksum' => uniqid('checksum_', true),
            'usage_type' => UploadUsageType::WorkContent->value,
            'is_committed' => false,
        ])->id;
    }

    private function createRegistrationMaterialFile(User $user, ?string $checksum = null): int
    {
        return (int) $user->uploadedFiles()->create([
            'disk' => 'local',
            'path' => 'uploads/registration-material/'.uniqid('material_', true).'.png',
            'url' => 'https://example.com/material.png',
            'mime_type' => 'image/png',
            'size' => 1024,
            'checksum' => $checksum ?? uniqid('checksum_', true),
            'usage_type' => UploadUsageType::RegistrationMaterial->value,
            'is_committed' => false,
        ])->id;
    }
}
