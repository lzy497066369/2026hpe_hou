<?php

namespace Tests\Feature\Api;

use App\Enums\LotteryResultStatus;
use App\Models\LotteryQualification;
use App\Models\LotteryRecord;
use App\Models\Prize;
use App\Models\User;
use App\Support\AwardLevels;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LotteryPoolTest extends TestCase
{
    use RefreshDatabase;

    public function test_lottery_draw_requires_published_qualification_for_official_pools(): void
    {
        $user = User::factory()->create();

        $this->withHeaders($this->authHeaders($user))
            ->postJson('/api/v1/lottery/draw', [
                'sourceType' => AwardLevels::FRAGRANCE_VOTE,
            ])
            ->assertStatus(422)
            ->assertJsonFragment(['message' => '抽奖次数不足']);
    }

    public function test_lottery_draw_uses_requested_pool_and_records_source_type(): void
    {
        $user = User::factory()->create();
        $this->createQualification($user, AwardLevels::DREAM_PARK);
        $prize = $this->createPrize(AwardLevels::DREAM_PARK, 3);

        $this->travelTo(now('Asia/Shanghai')->setDate(2026, 7, 10)->setTime(9, 0));

        $this->withHeaders($this->authHeaders($user))
            ->postJson('/api/v1/lottery/draw', [
                'sourceType' => AwardLevels::DREAM_PARK,
            ])
            ->assertOk()
            ->assertJsonPath('data.resultStatus', LotteryResultStatus::Won->value)
            ->assertJsonPath('data.sourceType', AwardLevels::DREAM_PARK)
            ->assertJsonPath('data.prize.level', AwardLevels::DREAM_PARK);

        $this->assertDatabaseHas('lottery_records', [
            'user_id' => $user->id,
            'prize_id' => $prize->id,
            'source_type' => AwardLevels::DREAM_PARK,
            'result_status' => LotteryResultStatus::Won->value,
        ]);
    }

    public function test_user_cannot_win_same_lottery_pool_twice(): void
    {
        $user = User::factory()->create();
        $this->createQualification($user, AwardLevels::FRAGRANCE_VOTE, 2);
        $this->createPrize(AwardLevels::FRAGRANCE_VOTE, 10);

        $this->travelTo(now('Asia/Shanghai')->setDate(2026, 7, 10)->setTime(9, 0));

        $headers = $this->authHeaders($user);

        $this->withHeaders($headers)
            ->postJson('/api/v1/lottery/draw', [
                'sourceType' => AwardLevels::FRAGRANCE_VOTE,
            ])
            ->assertOk();

        $this->withHeaders($headers)
            ->postJson('/api/v1/lottery/draw', [
                'sourceType' => AwardLevels::FRAGRANCE_VOTE,
            ])
            ->assertStatus(422)
            ->assertJsonFragment(['message' => '该奖项已中奖']);
    }

    public function test_losing_once_consumes_lottery_pool_qualification(): void
    {
        $user = User::factory()->create();
        $this->createQualification($user, AwardLevels::FRAGRANCE_VOTE, 3);

        $this->travelTo(now('Asia/Shanghai')->setDate(2026, 7, 10)->setTime(9, 0));

        $headers = $this->authHeaders($user);

        $this->withHeaders($headers)
            ->postJson('/api/v1/lottery/draw', [
                'sourceType' => AwardLevels::FRAGRANCE_VOTE,
            ])
            ->assertOk()
            ->assertJsonPath('data.resultStatus', LotteryResultStatus::Lost->value)
            ->assertJsonPath('data.sourceType', AwardLevels::FRAGRANCE_VOTE);

        $this->assertDatabaseHas('lottery_qualifications', [
            'user_id' => $user->id,
            'source_type' => AwardLevels::FRAGRANCE_VOTE,
            'chance_count' => 3,
            'used_count' => 3,
        ]);

        $this->withHeaders($headers)
            ->getJson('/api/v1/lottery/qualification?sourceType='.AwardLevels::FRAGRANCE_VOTE)
            ->assertOk()
            ->assertJsonPath('data.qualified', true)
            ->assertJsonPath('data.chanceCount', 3)
            ->assertJsonPath('data.usedCount', 3)
            ->assertJsonPath('data.reason', 'chance_used_up');

        $this->withHeaders($headers)
            ->postJson('/api/v1/lottery/draw', [
                'sourceType' => AwardLevels::FRAGRANCE_VOTE,
            ])
            ->assertStatus(422)
            ->assertJsonFragment(['message' => '抽奖次数不足']);
    }

    public function test_winning_one_pool_does_not_block_another_pool(): void
    {
        $user = User::factory()->create();
        $this->createQualification($user, AwardLevels::FRAGRANCE_VOTE);
        $this->createQualification($user, AwardLevels::DREAM_PARK);
        $this->createPrize(AwardLevels::FRAGRANCE_VOTE, 10);
        $this->createPrize(AwardLevels::DREAM_PARK, 3);

        $this->travelTo(now('Asia/Shanghai')->setDate(2026, 7, 10)->setTime(9, 0));

        $headers = $this->authHeaders($user);

        $this->withHeaders($headers)
            ->postJson('/api/v1/lottery/draw', [
                'sourceType' => AwardLevels::FRAGRANCE_VOTE,
            ])
            ->assertOk()
            ->assertJsonPath('data.sourceType', AwardLevels::FRAGRANCE_VOTE);

        $this->withHeaders($headers)
            ->postJson('/api/v1/lottery/draw', [
                'sourceType' => AwardLevels::DREAM_PARK,
            ])
            ->assertOk()
            ->assertJsonPath('data.sourceType', AwardLevels::DREAM_PARK);
    }

    public function test_qualification_can_be_queried_by_source_type(): void
    {
        $user = User::factory()->create();
        $this->createQualification($user, AwardLevels::FRAGRANCE_VOTE, 3, 1);

        $this->withHeaders($this->authHeaders($user))
            ->getJson('/api/v1/lottery/qualification?sourceType='.AwardLevels::FRAGRANCE_VOTE)
            ->assertOk()
            ->assertJsonPath('data.sourceType', AwardLevels::FRAGRANCE_VOTE)
            ->assertJsonPath('data.qualified', true)
            ->assertJsonPath('data.chanceCount', 3)
            ->assertJsonPath('data.usedCount', 1);
    }

    public function test_qualification_returns_not_published_when_no_record_exists(): void
    {
        $user = User::factory()->create();

        $this->withHeaders($this->authHeaders($user))
            ->getJson('/api/v1/lottery/qualification?sourceType='.AwardLevels::FRAGRANCE_VOTE)
            ->assertOk()
            ->assertJsonPath('data.sourceType', AwardLevels::FRAGRANCE_VOTE)
            ->assertJsonPath('data.qualified', false)
            ->assertJsonPath('data.reason', 'qualification_not_published');
    }

    public function test_qualification_returns_not_qualified_after_pool_has_been_published(): void
    {
        $qualifiedUser = User::factory()->create();
        $unqualifiedUser = User::factory()->create();
        $this->createQualification($qualifiedUser, AwardLevels::FRAGRANCE_VOTE);

        $this->withHeaders($this->authHeaders($unqualifiedUser))
            ->getJson('/api/v1/lottery/qualification?sourceType='.AwardLevels::FRAGRANCE_VOTE)
            ->assertOk()
            ->assertJsonPath('data.sourceType', AwardLevels::FRAGRANCE_VOTE)
            ->assertJsonPath('data.qualified', false)
            ->assertJsonPath('data.reason', 'qualification_not_qualified');
    }

    public function test_qualification_returns_chance_used_up_when_all_chances_are_consumed(): void
    {
        $user = User::factory()->create();
        $this->createQualification($user, AwardLevels::FRAGRANCE_VOTE, 1, 1);

        $this->withHeaders($this->authHeaders($user))
            ->getJson('/api/v1/lottery/qualification?sourceType='.AwardLevels::FRAGRANCE_VOTE)
            ->assertOk()
            ->assertJsonPath('data.sourceType', AwardLevels::FRAGRANCE_VOTE)
            ->assertJsonPath('data.qualified', true)
            ->assertJsonPath('data.reason', 'chance_used_up');
    }

    public function test_my_prizes_deduplicates_participation_awards_for_client_display(): void
    {
        $user = User::factory()->create();
        $participationPrize = $this->createPrize(AwardLevels::PARTICIPATION, 0);
        $gamePrize = $this->createPrize(AwardLevels::GAME_TOP, 10);

        LotteryRecord::query()->create([
            'user_id' => $user->id,
            'prize_id' => $participationPrize->id,
            'source_type' => AwardLevels::PARTICIPATION,
            'result_status' => LotteryResultStatus::Won->value,
            'drawn_at' => now(),
        ]);
        LotteryRecord::query()->create([
            'user_id' => $user->id,
            'prize_id' => $participationPrize->id,
            'source_type' => AwardLevels::PARTICIPATION,
            'result_status' => LotteryResultStatus::Won->value,
            'drawn_at' => now()->addMinute(),
        ]);
        LotteryRecord::query()->create([
            'user_id' => $user->id,
            'prize_id' => $gamePrize->id,
            'source_type' => AwardLevels::GAME_TOP,
            'result_status' => LotteryResultStatus::Won->value,
            'drawn_at' => now(),
        ]);

        $response = $this->withHeaders($this->authHeaders($user))
            ->getJson('/api/v1/lottery/prizes/mine')
            ->assertOk();

        $levels = collect($response->json('data'))->pluck('prize.level')->all();

        $this->assertCount(2, $levels);
        $this->assertSame(1, collect($levels)->filter(fn (string $level): bool => $level === AwardLevels::PARTICIPATION)->count());
        $this->assertContains(AwardLevels::GAME_TOP, $levels);
    }

    private function createQualification(User $user, string $sourceType, int $chanceCount = 1, int $usedCount = 0): void
    {
        LotteryQualification::query()->create([
            'user_id' => $user->id,
            'source_type' => $sourceType,
            'qualified' => true,
            'chance_count' => $chanceCount,
            'used_count' => $usedCount,
        ]);
    }

    private function createPrize(string $level, int $stock): Prize
    {
        return Prize::query()->create([
            'name' => $level,
            'level' => $level,
            'stock' => $stock,
            'status' => 'active',
        ]);
    }

    /**
     * @return array<string, string>
     */
    private function authHeaders(User $user): array
    {
        $token = $this->postJson('/api/v1/auth/login', [
            'employeeNo' => $user->employee_no,
            'email' => $user->email,
            'nickname' => 'lottery',
        ])->json('data.token');

        return ['Authorization' => 'Bearer '.$token];
    }
}
