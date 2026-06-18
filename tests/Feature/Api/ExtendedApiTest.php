<?php

namespace Tests\Feature\Api;

use App\Enums\WorkPublishStatus;
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
            'nickname' => 'demo',
            'password' => 'unused',
            'status' => 'active',
        ]);

        $token = $this->postJson('/api/v1/auth/login', [
            'employeeNo' => $user->employee_no,
            'email' => $user->email,
            'nickname' => $user->nickname,
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

        $this->withHeaders($headers)->getJson('/api/v1/works/'.$workId)
            ->assertOk()
            ->assertJsonPath('data.id', $workId)
            ->assertJsonPath('data.publishStatus', WorkPublishStatus::Hidden->value);

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
            'nickname' => 'admin',
            'password' => 'unused',
            'status' => 'active',
            'role' => 'admin',
        ]);

        User::query()->create([
            'name' => 'Participant',
            'email' => 'participant@example.com',
            'employee_no' => 'E0002',
            'nickname' => 'participant',
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
            'nickname' => $admin->nickname,
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

    public function test_user_keeps_only_one_best_game_record(): void
    {
        $user = User::query()->create([
            'name' => 'Game User',
            'email' => 'game@example.com',
            'employee_no' => 'E0099',
            'nickname' => 'game-user',
            'password' => 'unused',
            'status' => 'active',
        ]);

        $token = $this->postJson('/api/v1/auth/login', [
            'employeeNo' => $user->employee_no,
            'email' => $user->email,
            'nickname' => $user->nickname,
        ])->json('data.token');
        $headers = ['Authorization' => 'Bearer '.$token];

        $first = $this->withHeaders($headers)->postJson('/api/v1/game/records', [
            'distance' => 1000,
            'score' => 3000,
            'duration' => 60,
        ]);
        $first->assertOk()->assertJsonPath('data.score', 3000);

        $lower = $this->withHeaders($headers)->postJson('/api/v1/game/records', [
            'distance' => 900,
            'score' => 2800,
            'duration' => 55,
        ]);
        $lower
            ->assertOk()
            ->assertJsonPath('data.id', $first->json('data.id'))
            ->assertJsonPath('data.score', 3000)
            ->assertJsonPath('data.distance', 1000);

        $sameScoreLongerDistance = $this->withHeaders($headers)->postJson('/api/v1/game/records', [
            'distance' => 1200,
            'score' => 3000,
            'duration' => 65,
        ]);
        $sameScoreLongerDistance
            ->assertOk()
            ->assertJsonPath('data.id', $first->json('data.id'))
            ->assertJsonPath('data.score', 3000)
            ->assertJsonPath('data.distance', 1000);

        $betterScore = $this->withHeaders($headers)->postJson('/api/v1/game/records', [
            'distance' => 1100,
            'score' => 3600,
            'duration' => 70,
        ]);
        $betterScore
            ->assertOk()
            ->assertJsonPath('data.id', $first->json('data.id'))
            ->assertJsonPath('data.score', 3600)
            ->assertJsonPath('data.distance', 1100);

        $this->assertDatabaseCount('game_records', 1);
        $this->assertDatabaseHas('game_records', [
            'user_id' => $user->id,
            'score' => 3600,
            'distance' => 1100,
            'duration' => 70,
        ]);
    }

    public function test_approved_registration_user_still_needs_extra_quota_to_submit_more_than_one_work(): void
    {
        $user = User::query()->create([
            'name' => 'Approved User',
            'email' => 'approved@example.com',
            'employee_no' => 'E0003',
            'nickname' => 'approved',
            'password' => 'unused',
            'status' => 'active',
        ]);

        $user->registrationProfile()->create([
            'employee_no' => $user->employee_no,
            'name' => $user->name,
            'department' => 'Demo',
            'contact' => '13800000003',
            'audit_status' => RegistrationAuditStatus::Approved->value,
        ]);

        $token = $this->postJson('/api/v1/auth/login', [
            'employeeNo' => $user->employee_no,
            'email' => $user->email,
            'nickname' => $user->nickname,
        ])->json('data.token');
        $headers = ['Authorization' => 'Bearer '.$token];

        $firstFileId = $this->createCommittedWorkContentFile($user);
        $secondFileId = $this->createCommittedWorkContentFile($user);

        $this->withHeaders($headers)->postJson('/api/v1/works/submit', [
            'type' => 'traditional',
            'group' => 'employee',
            'title' => 'First Work',
            'description' => 'First demo work',
            'contentFileId' => $firstFileId,
        ])->assertOk();

        $this->withHeaders($headers)->postJson('/api/v1/works/submit', [
            'type' => 'ai',
            'group' => 'employee',
            'title' => 'Second Work',
            'description' => 'Second demo work',
            'contentFileId' => $secondFileId,
        ])
            ->assertStatus(422)
            ->assertJsonFragment(['message' => '当前可上传作品名额已用完，请申请更多名额']);
    }

    private function createCommittedWorkContentFile(User $user): int
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
}
