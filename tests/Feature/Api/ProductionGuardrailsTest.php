<?php

namespace Tests\Feature\Api;

use App\Enums\UploadUsageType;
use App\Enums\WorkGroup;
use App\Enums\WorkPublishStatus;
use App\Enums\WorkType;
use App\Models\LotteryQualification;
use App\Models\Prize;
use App\Models\UploadedFile;
use App\Models\User;
use App\Models\Work;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProductionGuardrailsTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_without_lottery_qualification_cannot_draw_prize(): void
    {
        $user = $this->createUser('E1001', 'lottery@example.com');
        $prize = Prize::query()->create([
            'name' => 'Demo Prize',
            'level' => 'demo',
            'stock' => 1,
            'status' => 'active',
        ]);

        $this->withHeaders($this->authHeaders($user))
            ->postJson('/api/v1/lottery/draw')
            ->assertStatus(422)
            ->assertJsonFragment(['message' => '抽奖次数不足']);

        $this->assertDatabaseMissing('lottery_records', [
            'user_id' => $user->id,
        ]);
        $this->assertDatabaseHas('prizes', [
            'id' => $prize->id,
            'stock' => 1,
        ]);
    }

    public function test_unqualified_user_cannot_draw_prize_even_with_qualification_record(): void
    {
        $user = $this->createUser('E1002', 'unqualified@example.com');
        LotteryQualification::query()->create([
            'user_id' => $user->id,
            'source_type' => 'manual',
            'qualified' => false,
            'chance_count' => 1,
            'used_count' => 0,
        ]);

        $this->withHeaders($this->authHeaders($user))
            ->postJson('/api/v1/lottery/draw')
            ->assertStatus(422)
            ->assertJsonFragment(['message' => '抽奖次数不足']);

        $this->assertDatabaseMissing('lottery_records', [
            'user_id' => $user->id,
        ]);
    }

    public function test_hidden_work_detail_is_not_publicly_readable(): void
    {
        $author = $this->createUser('E1003', 'author@example.com');
        $file = $this->createWorkContentFile($author);
        $work = Work::query()->create([
            'user_id' => $author->id,
            'type' => WorkType::Traditional->value,
            'group' => WorkGroup::Employee->value,
            'title' => 'Hidden Work',
            'description' => 'Should not be public',
            'content_file_id' => $file->id,
            'publish_status' => WorkPublishStatus::Hidden->value,
        ]);

        $this->getJson('/api/v1/works/'.$work->id)
            ->assertNotFound();
    }

    public function test_game_record_rejects_unreasonable_scripted_score(): void
    {
        $user = $this->createUser('E1004', 'game-score@example.com');

        $this->withHeaders($this->authHeaders($user))
            ->postJson('/api/v1/game/records', [
                'distance' => 999999999,
                'score' => 999999999,
                'duration' => 1,
            ])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['distance', 'score']);
    }

    public function test_login_is_rate_limited(): void
    {
        $user = $this->createUser('E1005', 'rate-limit@example.com');

        for ($attempt = 0; $attempt < 5; $attempt++) {
            $this->postJson('/api/v1/auth/login', [
                'employeeNo' => $user->employee_no,
                'email' => 'wrong@example.com',
                'nickname' => $user->nickname,
            ])->assertStatus(422);
        }

        $this->postJson('/api/v1/auth/login', [
            'employeeNo' => $user->employee_no,
            'email' => 'wrong@example.com',
            'nickname' => $user->nickname,
        ])->assertTooManyRequests();
    }

    /**
     * @return array<string, string>
     */
    private function authHeaders(User $user): array
    {
        $token = $this->postJson('/api/v1/auth/login', [
            'employeeNo' => $user->employee_no,
            'email' => $user->email,
            'nickname' => $user->nickname,
        ])->json('data.token');

        return ['Authorization' => 'Bearer '.$token];
    }

    private function createUser(string $employeeNo, string $email): User
    {
        return User::query()->create([
            'name' => 'Demo User '.$employeeNo,
            'email' => $email,
            'employee_no' => $employeeNo,
            'nickname' => 'demo-'.$employeeNo,
            'password' => 'unused',
            'status' => 'active',
        ]);
    }

    private function createWorkContentFile(User $user): UploadedFile
    {
        return UploadedFile::query()->create([
            'user_id' => $user->id,
            'disk' => 'local',
            'path' => 'uploads/work-content/'.uniqid('work_', true).'.png',
            'url' => 'https://example.com/work.png',
            'mime_type' => 'image/png',
            'size' => 1024,
            'checksum' => uniqid('checksum_', true),
            'usage_type' => UploadUsageType::WorkContent->value,
            'is_committed' => true,
        ]);
    }
}
