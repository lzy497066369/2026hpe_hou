<?php

namespace Tests\Feature\Api;

use App\Enums\WorkPublishStatus;
use App\Enums\RegistrationAuditStatus;
use App\Enums\UploadUsageType;
use App\Enums\WorkGroup;
use App\Enums\WorkType;
use App\Models\GamePlayLog;
use App\Models\GameRecord;
use App\Models\User;
use App\Models\Work;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile as HttpUploadedFile;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ExtendedApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_manage_profile_and_works_and_game_records(): void
    {
        Storage::fake('public');

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
            ->assertJsonPath('data.phone', '13800000001');

        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'name' => 'Demo User',
            'phone' => '13800000001',
            'address' => 'Beijing',
        ]);

        $fileId = $this->withHeaders($headers)->post('/api/v1/uploads/local', [
            'usageType' => UploadUsageType::WorkContent->value,
            'file' => HttpUploadedFile::fake()->image('work.png'),
        ])->json('data.id');

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
            ->assertJsonPath('data.mine.score', 3600)
            ->assertJsonPath('data.mine.nickname', 'demo')
            ->assertJsonPath('data.mine.employeeNo', 'E0001');

        $this->withHeaders($headers)->postJson('/api/v1/auth/logout')
            ->assertOk()
            ->assertJsonPath('data.success', true);
    }

    public function test_admin_can_read_statistics_overview(): void
    {
        $this->travelTo(now('Asia/Shanghai')->setDate(2026, 6, 20)->setTime(12, 0));

        $admin = User::query()->create([
            'name' => 'Admin',
            'email' => 'admin@example.com',
            'employee_no' => 'A0001',
            'nickname' => 'admin',
            'password' => 'unused',
            'status' => 'active',
            'role' => 'admin',
            'last_login_at' => now(),
        ]);

        $participant = User::query()->create([
            'name' => 'Participant',
            'email' => 'participant@example.com',
            'employee_no' => 'E0002',
            'nickname' => 'participant',
            'password' => 'unused',
            'status' => 'active',
            'last_login_at' => now()->subDay(),
        ])->registrationProfile()->create([
            'employee_no' => 'E0002',
            'name' => 'Participant',
            'department' => 'Demo',
            'contact' => '13800000002',
            'audit_status' => RegistrationAuditStatus::Approved->value,
        ]);
        $participant = User::query()->where('employee_no', 'E0002')->firstOrFail();
        $gameParticipant = User::query()->create([
            'name' => 'Game Participant',
            'email' => 'game-participant@example.com',
            'employee_no' => 'E0003',
            'nickname' => 'game-participant',
            'password' => 'unused',
            'status' => 'active',
        ]);

        $traditionalEmployeeWork = $this->createOverviewWork($participant, WorkType::Traditional->value, WorkGroup::Employee->value, now());
        $this->createOverviewWork($participant, WorkType::Traditional->value, WorkGroup::Children->value, now()->subDay());
        $this->createOverviewWork($participant, WorkType::Ai->value, WorkGroup::Employee->value, now()->subDays(2));
        $this->createOverviewWork($participant, WorkType::Ai->value, WorkGroup::Children->value, now()->subDays(13));
        $this->createOverviewWork($participant, WorkType::Ai->value, WorkGroup::Children->value, now()->subDays(20));

        GameRecord::query()->create([
            'user_id' => $participant->id,
            'distance' => 1000,
            'score' => 5000,
            'duration' => 60,
            'played_at' => now(),
        ]);
        GamePlayLog::query()->create([
            'user_id' => $participant->id,
            'distance' => 1000,
            'score' => 5000,
            'duration' => 60,
            'played_at' => now(),
        ]);
        GamePlayLog::query()->create([
            'user_id' => $participant->id,
            'distance' => 800,
            'score' => 3500,
            'duration' => 55,
            'played_at' => now(),
        ]);
        GameRecord::query()->create([
            'user_id' => $gameParticipant->id,
            'distance' => 900,
            'score' => 3000,
            'duration' => 60,
            'played_at' => now()->subDay(),
        ]);
        GamePlayLog::query()->create([
            'user_id' => $gameParticipant->id,
            'distance' => 900,
            'score' => 3000,
            'duration' => 60,
            'played_at' => now()->subDay(),
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
                    'todayGamePlayCount',
                    'todayWorkUploadCount',
                    'todayLoginUserCount',
                    'claimableParticipationAwardCount',
                    'traditionalEmployeeWorkCount',
                    'traditionalChildrenWorkCount',
                    'aiEmployeeWorkCount',
                    'aiChildrenWorkCount',
                    'gamePlayTrend',
                    'workUploadTrend',
                    'gameParticipantCount',
                    'gamePlayTotalCount',
                ],
            ])
            ->assertJsonPath('data.loginUserCount', 2)
            ->assertJsonPath('data.todayGamePlayCount', 2)
            ->assertJsonPath('data.todayWorkUploadCount', 1)
            ->assertJsonPath('data.todayLoginUserCount', 1)
            ->assertJsonPath('data.claimableParticipationAwardCount', 5)
            ->assertJsonPath('data.traditionalEmployeeWorkCount', 1)
            ->assertJsonPath('data.traditionalChildrenWorkCount', 1)
            ->assertJsonPath('data.aiEmployeeWorkCount', 1)
            ->assertJsonPath('data.aiChildrenWorkCount', 2)
            ->assertJsonPath('data.workUploadTrend.13.date', now()->toDateString())
            ->assertJsonPath('data.workUploadTrend.13.count', 1)
            ->assertJsonPath('data.gamePlayTrend.13.date', now()->toDateString())
            ->assertJsonPath('data.gamePlayTrend.13.count', 2)
            ->assertJsonPath('data.gameParticipantCount', 2)
            ->assertJsonPath('data.gamePlayTotalCount', 3);

        $this->assertSame(WorkPublishStatus::Published->value, $traditionalEmployeeWork->publish_status);

        $this->travelBack();
    }

    public function test_admin_statistics_overview_survives_before_last_login_at_migration_is_applied(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->dropIndex('users_last_login_at_index');
            $table->dropColumn('last_login_at');
        });

        $admin = User::query()->create([
            'name' => 'Admin',
            'email' => 'admin@example.com',
            'employee_no' => 'A0001',
            'nickname' => 'admin',
            'password' => 'unused',
            'status' => 'active',
            'role' => 'admin',
        ]);

        $token = $this->postJson('/api/v1/auth/login', [
            'employeeNo' => $admin->employee_no,
            'email' => $admin->email,
            'nickname' => $admin->nickname,
        ])->json('data.token');

        $this->withHeaders(['Authorization' => 'Bearer '.$token])
            ->getJson('/api/v1/admin/statistics/overview')
            ->assertOk()
            ->assertJsonPath('data.loginUserCount', 0)
            ->assertJsonPath('data.todayLoginUserCount', 0);
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
        $this->assertDatabaseCount('game_play_logs', 4);
        $this->assertDatabaseHas('game_records', [
            'user_id' => $user->id,
            'score' => 3600,
            'distance' => 1100,
            'duration' => 70,
        ]);
        $this->assertDatabaseHas('game_play_logs', [
            'user_id' => $user->id,
            'score' => 2800,
            'distance' => 900,
            'duration' => 55,
        ]);
    }

    public function test_user_can_submit_high_score_at_current_limit(): void
    {
        $user = User::query()->create([
            'name' => 'High Score User',
            'email' => 'high-score@example.com',
            'employee_no' => 'E0100',
            'nickname' => 'highscore',
            'password' => 'unused',
            'status' => 'active',
        ]);

        $token = $this->postJson('/api/v1/auth/login', [
            'employeeNo' => $user->employee_no,
            'email' => $user->email,
            'nickname' => $user->nickname,
        ])->json('data.token');
        $headers = ['Authorization' => 'Bearer '.$token];

        $this->withHeaders($headers)->postJson('/api/v1/game/records', [
            'distance' => 8200,
            'score' => 100000000,
            'duration' => 420,
        ])
            ->assertOk()
            ->assertJsonPath('data.score', 100000000)
            ->assertJsonPath('data.distance', 8200);

        $this->withHeaders($headers)->getJson('/api/v1/game/rankings')
            ->assertOk()
            ->assertJsonPath('data.items.0.score', 100000000)
            ->assertJsonPath('data.mine.score', 100000000)
            ->assertJsonPath('data.mine.nickname', 'highscore')
            ->assertJsonPath('data.mine.employeeNo', 'E0100');
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

    private function createOverviewWork(User $user, string $type, string $group, \DateTimeInterface $createdAt): Work
    {
        $work = Work::query()->create([
            'user_id' => $user->id,
            'type' => $type,
            'group' => $group,
            'title' => $type.' '.$group,
            'description' => 'Overview work',
            'audit_status' => 'published',
            'publish_status' => WorkPublishStatus::Published->value,
            'vote_count' => 0,
        ]);

        $work->timestamps = false;
        $work->forceFill([
            'created_at' => $createdAt,
            'updated_at' => $createdAt,
        ])->save();

        return $work->refresh();
    }
}
