<?php

namespace Tests\Feature\Api;

use App\Enums\RegistrationAuditStatus;
use App\Enums\UploadUsageType;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExtendedApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_manage_profile_and_works_and_game_records(): void
    {
        $user = User::query()->create([
            'name' => 'Demo User',
            'email' => 'demo@example.com',
            'employee_no' => 'E0001',
            'password' => 'unused',
            'status' => 'active',
        ]);

        $token = $this->postJson('/api/v1/auth/login', [
            'employeeNo' => $user->employee_no,
            'email' => $user->email,
        ])->json('data.token');
        $headers = ['Authorization' => 'Bearer '.$token];

        $this->withHeaders($headers)->getJson('/api/v1/auth/me')
            ->assertOk()
            ->assertJsonPath('data.employeeNo', 'E0001');

        $this->withHeaders($headers)->patchJson('/api/v1/profile', [
            'name' => 'Updated User',
            'phone' => '13800000001',
            'address' => 'Beijing',
        ])
            ->assertOk()
            ->assertJsonPath('data.name', 'Updated User')
            ->assertJsonPath('data.phone', '13800000001');

        $fileId = $this->withHeaders($headers)->postJson('/api/v1/uploads/policy', [
            'usageType' => UploadUsageType::WorkContent->value,
        ])->json('data.fileId');

        $this->withHeaders($headers)->postJson('/api/v1/uploads/complete', ['fileId' => $fileId])->assertOk();

        $work = $this->withHeaders($headers)->postJson('/api/v1/works/submit', [
            'type' => 'traditional',
            'group' => 'employee',
            'title' => 'My Work',
            'description' => 'A demo work',
            'contentFileId' => $fileId,
        ]);

        $work
            ->assertOk()
            ->assertJsonPath('data.title', 'My Work')
            ->assertJsonPath('data.auditStatus', 'submitted');

        $workId = $work->json('data.id');

        $this->withHeaders($headers)->patchJson('/api/v1/works/'.$workId, [
            'title' => 'Updated Work',
            'description' => 'Updated description',
        ])
            ->assertOk()
            ->assertJsonPath('data.title', 'Updated Work');

        $this->withHeaders($headers)->getJson('/api/v1/works/mine')
            ->assertOk()
            ->assertJsonPath('data.items.0.id', $workId);

        $this->withHeaders($headers)->postJson('/api/v1/game/records', [
            'distance' => 1200,
            'score' => 3600,
            'duration' => 90,
        ])
            ->assertOk()
            ->assertJsonPath('data.score', 3600)
            ->assertJsonPath('data.rank', 1);

        $this->withHeaders($headers)->getJson('/api/v1/game/rankings')
            ->assertOk()
            ->assertJsonPath('data.items.0.score', 3600)
            ->assertJsonPath('data.mine.score', 3600);

        $this->withHeaders($headers)->postJson('/api/v1/auth/logout')
            ->assertOk()
            ->assertJsonPath('data.success', true);
    }

    public function test_admin_can_read_statistics_overview(): void
    {
        $admin = User::query()->create([
            'name' => 'Admin',
            'email' => 'admin@example.com',
            'employee_no' => 'A0001',
            'password' => 'unused',
            'status' => 'active',
            'role' => 'admin',
        ]);

        User::query()->create([
            'name' => 'Participant',
            'email' => 'participant@example.com',
            'employee_no' => 'E0002',
            'password' => 'unused',
            'status' => 'active',
        ])->registrationProfile()->create([
            'employee_no' => 'E0002',
            'name' => 'Participant',
            'department' => 'Demo',
            'contact' => '13800000002',
            'audit_status' => RegistrationAuditStatus::Approved->value,
        ]);

        $token = $this->postJson('/api/v1/auth/login', [
            'employeeNo' => $admin->employee_no,
            'email' => $admin->email,
        ])->json('data.token');

        $this->withHeaders(['Authorization' => 'Bearer '.$token])
            ->getJson('/api/v1/admin/statistics/overview')
            ->assertOk()
            ->assertJsonStructure([
                'data' => [
                    'loginUserCount',
                    'workParticipantCount',
                    'workTotalCount',
                    'workCountsByTrack',
                    'gameParticipantCount',
                    'gamePlayTotalCount',
                ],
            ]);
    }
}
