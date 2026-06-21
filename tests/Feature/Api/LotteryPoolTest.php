<?php

namespace Tests\Feature\Api;

use App\Enums\LotteryResultStatus;
use App\Models\LotteryQualification;
use App\Models\LotteryRecord;
use App\Models\Prize;
use App\Models\User;
use App\Models\Work;
use App\Models\WorkVote;
use App\Services\Lottery\LotteryService;
use App\Services\Support\AtomicLock;
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

    public function test_dream_park_draw_loses_when_probability_pick_exceeds_remaining_stock(): void
    {
        $currentUser = User::factory()->create();
        $this->createQualification($currentUser, AwardLevels::DREAM_PARK);
        User::factory()
            ->count(9)
            ->create()
            ->each(fn (User $user) => $this->createQualification($user, AwardLevels::DREAM_PARK));
        $prize = $this->createPrize(AwardLevels::DREAM_PARK, 3);

        $service = new class(app(AtomicLock::class)) extends LotteryService
        {
            protected function randomWeight(int $max): int
            {
                return 4;
            }
        };

        $result = $service->draw($currentUser, AwardLevels::DREAM_PARK);

        $this->assertSame(LotteryResultStatus::Lost->value, $result['resultStatus']);
        $this->assertNull($result['prize']);
        $this->assertDatabaseHas('prizes', [
            'id' => $prize->id,
            'stock' => 3,
        ]);
        $this->assertDatabaseHas('lottery_records', [
            'user_id' => $currentUser->id,
            'source_type' => AwardLevels::DREAM_PARK,
            'result_status' => LotteryResultStatus::Lost->value,
        ]);
    }

    public function test_dream_park_draw_wins_when_probability_pick_is_within_remaining_stock(): void
    {
        $currentUser = User::factory()->create();
        $this->createQualification($currentUser, AwardLevels::DREAM_PARK);
        User::factory()
            ->count(9)
            ->create()
            ->each(fn (User $user) => $this->createQualification($user, AwardLevels::DREAM_PARK));
        $prize = $this->createPrize(AwardLevels::DREAM_PARK, 3);

        $service = new class(app(AtomicLock::class)) extends LotteryService
        {
            protected function randomWeight(int $max): int
            {
                return 3;
            }
        };

        $result = $service->draw($currentUser, AwardLevels::DREAM_PARK);

        $this->assertSame(LotteryResultStatus::Won->value, $result['resultStatus']);
        $this->assertSame((string) $prize->id, $result['prize']['id']);
        $this->assertDatabaseHas('prizes', [
            'id' => $prize->id,
            'stock' => 2,
        ]);
    }

    public function test_user_cannot_win_same_lottery_pool_twice(): void
    {
        $user = User::factory()->create();
        $target = User::factory()->create();
        $targetWork = $this->createWork($target);
        $this->createQualification($user, AwardLevels::FRAGRANCE_VOTE, 2);
        $this->createPrize(AwardLevels::FRAGRANCE_VOTE, 10);
        $this->createVotes($user, $targetWork, 2);

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

    public function test_fragrance_draw_uses_vote_weight_to_decide_current_user_win_probability(): void
    {
        $currentUser = User::factory()->create();
        $highWeightUser = User::factory()->create();
        $target = User::factory()->create();
        $targetWork = $this->createWork($target);

        $this->createQualification($currentUser, AwardLevels::FRAGRANCE_VOTE);
        $this->createQualification($highWeightUser, AwardLevels::FRAGRANCE_VOTE);
        $prize = $this->createPrize(AwardLevels::FRAGRANCE_VOTE, 1);
        $this->createVotes($currentUser, $targetWork, 1);
        $this->createVotes($highWeightUser, $targetWork, 5);

        $service = new class(app(AtomicLock::class)) extends LotteryService
        {
            protected function randomWeight(int $max): int
            {
                return 2;
            }
        };

        $result = $service->draw($currentUser, AwardLevels::FRAGRANCE_VOTE);

        $this->assertSame(LotteryResultStatus::Lost->value, $result['resultStatus']);
        $this->assertDatabaseHas('prizes', [
            'id' => $prize->id,
            'stock' => 1,
        ]);
        $this->assertDatabaseHas('lottery_records', [
            'user_id' => $currentUser->id,
            'source_type' => AwardLevels::FRAGRANCE_VOTE,
            'result_status' => LotteryResultStatus::Lost->value,
        ]);
    }

    public function test_fragrance_draw_wins_when_weighted_pick_selects_current_user(): void
    {
        $currentUser = User::factory()->create();
        $target = User::factory()->create();
        $targetWork = $this->createWork($target);

        $this->createQualification($currentUser, AwardLevels::FRAGRANCE_VOTE);
        $prize = $this->createPrize(AwardLevels::FRAGRANCE_VOTE, 1);
        $this->createVotes($currentUser, $targetWork, 3);

        $service = new class(app(AtomicLock::class)) extends LotteryService
        {
            protected function randomWeight(int $max): int
            {
                return $max;
            }
        };

        $result = $service->draw($currentUser, AwardLevels::FRAGRANCE_VOTE);

        $this->assertSame(LotteryResultStatus::Won->value, $result['resultStatus']);
        $this->assertSame((string) $prize->id, $result['prize']['id']);
        $this->assertDatabaseHas('prizes', [
            'id' => $prize->id,
            'stock' => 0,
        ]);
    }

    public function test_fragrance_draw_uses_probability_formula_with_remaining_stock(): void
    {
        $currentUser = User::factory()->create();
        $otherUser = User::factory()->create();
        $target = User::factory()->create();
        $targetWork = $this->createWork($target);

        $this->createQualification($currentUser, AwardLevels::FRAGRANCE_VOTE);
        $this->createQualification($otherUser, AwardLevels::FRAGRANCE_VOTE);
        $prize = $this->createPrize(AwardLevels::FRAGRANCE_VOTE, 2);
        $this->createVotes($currentUser, $targetWork, 1);
        $this->createVotes($otherUser, $targetWork, 5);

        $service = new class(app(AtomicLock::class)) extends LotteryService
        {
            protected function randomWeight(int $max): int
            {
                return 2;
            }
        };

        $result = $service->draw($currentUser, AwardLevels::FRAGRANCE_VOTE);

        $this->assertSame(LotteryResultStatus::Won->value, $result['resultStatus']);
        $this->assertSame((string) $prize->id, $result['prize']['id']);
        $this->assertDatabaseHas('prizes', [
            'id' => $prize->id,
            'stock' => 1,
        ]);
    }

    public function test_fragrance_draw_does_not_auto_win_when_remaining_stock_covers_remaining_candidates(): void
    {
        $currentUser = User::factory()->create();
        $otherUser = User::factory()->create();
        $target = User::factory()->create();
        $targetWork = $this->createWork($target);

        $this->createQualification($currentUser, AwardLevels::FRAGRANCE_VOTE);
        $this->createQualification($otherUser, AwardLevels::FRAGRANCE_VOTE);
        $prize = $this->createPrize(AwardLevels::FRAGRANCE_VOTE, 2);
        $this->createVotes($currentUser, $targetWork, 1);
        $this->createVotes($otherUser, $targetWork, 5);

        $service = new class(app(AtomicLock::class)) extends LotteryService
        {
            protected function randomWeight(int $max): int
            {
                return 3;
            }
        };

        $result = $service->draw($currentUser, AwardLevels::FRAGRANCE_VOTE);

        $this->assertSame(LotteryResultStatus::Lost->value, $result['resultStatus']);
        $this->assertDatabaseHas('prizes', [
            'id' => $prize->id,
            'stock' => 2,
        ]);
    }

    public function test_fragrance_draw_removes_already_drawn_users_from_remaining_weight_pool(): void
    {
        $currentUser = User::factory()->create();
        $alreadyDrawnUser = User::factory()->create();
        $target = User::factory()->create();
        $targetWork = $this->createWork($target);

        $this->createQualification($currentUser, AwardLevels::FRAGRANCE_VOTE);
        $this->createQualification($alreadyDrawnUser, AwardLevels::FRAGRANCE_VOTE);
        $prize = $this->createPrize(AwardLevels::FRAGRANCE_VOTE, 1);
        $this->createVotes($currentUser, $targetWork, 1);
        $this->createVotes($alreadyDrawnUser, $targetWork, 100);
        LotteryRecord::query()->create([
            'user_id' => $alreadyDrawnUser->id,
            'prize_id' => null,
            'source_type' => AwardLevels::FRAGRANCE_VOTE,
            'result_status' => LotteryResultStatus::Lost->value,
            'drawn_at' => now(),
        ]);

        $service = new class(app(AtomicLock::class)) extends LotteryService
        {
            protected function randomWeight(int $max): int
            {
                return $max;
            }
        };

        $result = $service->draw($currentUser, AwardLevels::FRAGRANCE_VOTE);

        $this->assertSame(LotteryResultStatus::Won->value, $result['resultStatus']);
        $this->assertSame((string) $prize->id, $result['prize']['id']);
    }

    public function test_fragrance_draw_only_counts_votes_for_other_users_works(): void
    {
        $currentUser = User::factory()->create();
        $currentUserWork = $this->createWork($currentUser);

        $this->createQualification($currentUser, AwardLevels::FRAGRANCE_VOTE);
        $prize = $this->createPrize(AwardLevels::FRAGRANCE_VOTE, 1);
        $this->createVotes($currentUser, $currentUserWork, 3);

        $result = app(LotteryService::class)->draw($currentUser, AwardLevels::FRAGRANCE_VOTE);

        $this->assertSame(LotteryResultStatus::Lost->value, $result['resultStatus']);
        $this->assertDatabaseHas('prizes', [
            'id' => $prize->id,
            'stock' => 1,
        ]);
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

    private function createWork(User $user): Work
    {
        return Work::query()->create([
            'user_id' => $user->id,
            'type' => 'traditional',
            'group' => 'employee',
            'title' => 'Vote Target '.$user->employee_no,
            'description' => 'Vote target',
            'publish_status' => 'published',
        ]);
    }

    private function createVotes(User $user, Work $work, int $count): void
    {
        for ($index = 0; $index < $count; $index++) {
            WorkVote::query()->create([
                'user_id' => $user->id,
                'work_id' => $work->id,
                'vote_date' => now()->subDays($index)->toDateString(),
                'source' => 'h5',
            ]);
        }
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
