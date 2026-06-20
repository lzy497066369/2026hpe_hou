<?php

namespace Tests\Feature\Admin;

use App\Enums\LotteryResultStatus;
use App\Enums\WorkGroup;
use App\Enums\WorkPublishStatus;
use App\Enums\WorkType;
use App\Models\GameRecord;
use App\Models\LotteryQualification;
use App\Models\LotteryRecord;
use App\Models\Prize;
use App\Models\User;
use App\Models\Work;
use App\Models\WorkVote;
use App\Services\Admin\AwardSettlementService;
use App\Support\AwardLevels;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AwardSettlementServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_talent_awards_use_four_tracks_and_include_boundary_ties(): void
    {
        $tracks = [
            [WorkType::Traditional->value, WorkGroup::Employee->value],
            [WorkType::Traditional->value, WorkGroup::Children->value],
            [WorkType::Ai->value, WorkGroup::Employee->value],
            [WorkType::Ai->value, WorkGroup::Children->value],
        ];

        foreach ($tracks as [$type, $group]) {
            for ($rank = 1; $rank <= 16; $rank++) {
                $user = User::factory()->create();
                $this->createWork($user, $type, $group, $rank >= 15 ? 86 : 120 - $rank);
            }
        }

        $hiddenUser = User::factory()->create();
        $this->createWork(
            $hiddenUser,
            WorkType::Traditional->value,
            WorkGroup::Employee->value,
            999,
            WorkPublishStatus::Hidden->value
        );

        $service = app(AwardSettlementService::class);
        $preview = $service->previewTalentAwards();
        $result = $service->awardTalentAwards();

        $this->assertCount(64, $preview);
        $this->assertNotNull($preview[0]['work_id'] ?? null);
        $this->assertSame(64, $result['count']);
        $this->assertDatabaseCount('lottery_records', 64);
        $this->assertDatabaseHas('lottery_records', [
            'user_id' => $preview[0]['user_id'],
            'work_id' => $preview[0]['work_id'],
        ]);
        $this->assertDatabaseMissing('lottery_records', [
            'user_id' => $hiddenUser->id,
        ]);
    }

    public function test_game_awards_include_score_ties_at_tenth_place(): void
    {
        for ($rank = 1; $rank <= 11; $rank++) {
            $user = User::factory()->create();
            GameRecord::query()->create([
                'user_id' => $user->id,
                'distance' => 1000 + $rank,
                'score' => $rank >= 10 ? 500 : 1000 - $rank,
                'duration' => 60,
                'played_at' => now(),
            ]);
        }

        $service = app(AwardSettlementService::class);
        $preview = $service->previewGameAwards();
        $result = $service->awardGameAwards();

        $this->assertCount(11, $preview);
        $this->assertSame(11, $result['count']);
        $this->assertDatabaseCount('lottery_records', 11);
    }

    public function test_fixed_awards_include_all_entries_when_below_rank_limit(): void
    {
        $talentUser = User::factory()->create();
        $gameUser = User::factory()->create();
        $this->createWork($talentUser);
        GameRecord::query()->create([
            'user_id' => $gameUser->id,
            'distance' => 900,
            'score' => 3000,
            'duration' => 60,
            'played_at' => now(),
        ]);

        $service = app(AwardSettlementService::class);

        $this->assertCount(1, $service->previewTalentAwards());
        $this->assertCount(1, $service->previewGameAwards());
    }

    public function test_participation_award_requires_published_work_and_game_and_excludes_top_awards(): void
    {
        $eligible = User::factory()->create(['employee_no' => 'E2001']);
        $onlyWork = User::factory()->create(['employee_no' => 'E2002']);
        $onlyGame = User::factory()->create(['employee_no' => 'E2003']);
        $hiddenWork = User::factory()->create(['employee_no' => 'E2004']);
        $talentWinner = User::factory()->create(['employee_no' => 'E2005']);
        $gameWinner = User::factory()->create(['employee_no' => 'E2006']);

        $eligibleFirstWork = $this->createWork($eligible, voteCount: 12);
        $eligibleSecondWork = $this->createWork($eligible, voteCount: 11);
        $this->createGameRecord($eligible);
        $this->createWork($onlyWork);
        $this->createGameRecord($onlyGame);
        $this->createWork($hiddenWork, publishStatus: WorkPublishStatus::Hidden->value);
        $this->createGameRecord($hiddenWork);
        $this->createWork($talentWinner);
        $this->createGameRecord($talentWinner);
        $this->createWork($gameWinner);
        $this->createGameRecord($gameWinner);

        $talentPrize = Prize::query()->create([
            'name' => '才艺大赛奖',
            'level' => AwardLevels::TALENT_TOP,
            'stock' => 60,
            'status' => 'active',
        ]);
        $gamePrize = Prize::query()->create([
            'name' => '线上小游戏奖',
            'level' => AwardLevels::GAME_TOP,
            'stock' => 10,
            'status' => 'active',
        ]);
        $this->createWonRecord($talentWinner, $talentPrize);
        $this->createWonRecord($gameWinner, $gamePrize);

        $service = app(AwardSettlementService::class);
        $preview = $service->previewParticipationAwards();
        $result = $service->awardParticipationAwards();

        $this->assertSame([$eligibleFirstWork->id, $eligibleSecondWork->id], array_column($preview, 'work_id'));
        $this->assertSame([$eligible->id, $eligible->id], array_column($preview, 'user_id'));
        $this->assertSame(2, $result['count']);
        $this->assertDatabaseHas('lottery_records', [
            'user_id' => $eligible->id,
            'work_id' => $eligibleFirstWork->id,
            'result_status' => LotteryResultStatus::Won->value,
        ]);
        $this->assertDatabaseHas('lottery_records', [
            'user_id' => $eligible->id,
            'work_id' => $eligibleSecondWork->id,
            'result_status' => LotteryResultStatus::Won->value,
        ]);
    }

    public function test_fragrance_candidates_show_weight_and_ineligible_reason(): void
    {
        $voter = User::factory()->create(['employee_no' => 'E3001']);
        $noVote = User::factory()->create(['employee_no' => 'E3002']);
        $alreadyWon = User::factory()->create(['employee_no' => 'E3003']);
        $target = User::factory()->create();
        $targetWork = $this->createWork($target);

        $this->createVotes($voter, $targetWork, 3);
        $this->createVotes($alreadyWon, $targetWork, 1);
        $this->createWonRecord($alreadyWon, Prize::query()->create([
            'name' => '手有余香奖',
            'level' => AwardLevels::FRAGRANCE_VOTE,
            'stock' => 10,
            'status' => 'active',
        ]));

        $candidates = collect(app(AwardSettlementService::class)->fragranceCandidates())->keyBy('user_id');

        $this->assertTrue($candidates[$voter->id]['eligible']);
        $this->assertSame(3, $candidates[$voter->id]['weight']);
        $this->assertFalse($candidates[$noVote->id]['eligible']);
        $this->assertSame('未为他人投票', $candidates[$noVote->id]['reason']);
        $this->assertFalse($candidates[$alreadyWon->id]['eligible']);
        $this->assertSame('已获得手有余香奖', $candidates[$alreadyWon->id]['reason']);
    }

    public function test_dream_park_candidates_show_missing_reasons_and_allow_existing_top_winners(): void
    {
        $eligible = User::factory()->create(['employee_no' => 'E4001']);
        $missingWork = User::factory()->create(['employee_no' => 'E4002']);
        $topWinner = User::factory()->create(['employee_no' => 'E4003']);
        $target = User::factory()->create();
        $targetWork = $this->createWork($target);

        foreach ([$eligible, $topWinner] as $user) {
            $this->createWork($user);
            $this->createGameRecord($user);
            $this->createVotes($user, $targetWork, 1);
        }
        $this->createGameRecord($missingWork);
        $this->createVotes($missingWork, $targetWork, 1);
        $this->createWonRecord($topWinner, Prize::query()->create([
            'name' => '才艺大赛奖',
            'level' => AwardLevels::TALENT_TOP,
            'stock' => 60,
            'status' => 'active',
        ]));

        $candidates = collect(app(AwardSettlementService::class)->dreamParkCandidates())->keyBy('user_id');

        $this->assertTrue($candidates[$eligible->id]['eligible']);
        $this->assertTrue($candidates[$topWinner->id]['eligible']);
        $this->assertFalse($candidates[$missingWork->id]['eligible']);
        $this->assertSame('未发布作品', $candidates[$missingWork->id]['reason']);
    }

    public function test_publish_fragrance_qualifications_creates_weighted_records_and_is_idempotent(): void
    {
        $eligible = User::factory()->create(['employee_no' => 'E5001']);
        $noVote = User::factory()->create(['employee_no' => 'E5002']);
        $alreadyWon = User::factory()->create(['employee_no' => 'E5003']);
        $target = User::factory()->create();
        $targetWork = $this->createWork($target);

        $this->createVotes($eligible, $targetWork, 3);
        $this->createVotes($alreadyWon, $targetWork, 1);
        $this->createWonRecord($alreadyWon, Prize::query()->create([
            'name' => '手有余香奖',
            'level' => AwardLevels::FRAGRANCE_VOTE,
            'stock' => 10,
            'status' => 'active',
        ]));

        $service = app(AwardSettlementService::class);

        $first = $service->publishFragranceQualifications();
        $second = $service->publishFragranceQualifications();

        $this->assertSame(1, $first['count']);
        $this->assertSame(1, $second['count']);
        $this->assertDatabaseCount('lottery_qualifications', 1);
        $this->assertDatabaseHas('lottery_qualifications', [
            'user_id' => $eligible->id,
            'source_type' => AwardLevels::FRAGRANCE_VOTE,
            'qualified' => true,
            'chance_count' => 3,
            'used_count' => 0,
        ]);
        $this->assertDatabaseMissing('lottery_qualifications', [
            'user_id' => $noVote->id,
            'source_type' => AwardLevels::FRAGRANCE_VOTE,
        ]);
        $this->assertDatabaseMissing('lottery_qualifications', [
            'user_id' => $alreadyWon->id,
            'source_type' => AwardLevels::FRAGRANCE_VOTE,
        ]);
    }

    public function test_publish_dream_park_qualifications_only_includes_eligible_users(): void
    {
        $eligible = User::factory()->create(['employee_no' => 'E6001']);
        $topWinner = User::factory()->create(['employee_no' => 'E6002']);
        $missingWork = User::factory()->create(['employee_no' => 'E6003']);
        $alreadyWon = User::factory()->create(['employee_no' => 'E6004']);
        $target = User::factory()->create();
        $targetWork = $this->createWork($target);

        foreach ([$eligible, $topWinner, $alreadyWon] as $user) {
            $this->createWork($user);
            $this->createGameRecord($user);
            $this->createVotes($user, $targetWork, 1);
        }

        $this->createGameRecord($missingWork);
        $this->createVotes($missingWork, $targetWork, 1);

        $this->createWonRecord($topWinner, Prize::query()->create([
            'name' => '才艺大赛奖',
            'level' => AwardLevels::TALENT_TOP,
            'stock' => 60,
            'status' => 'active',
        ]));
        $this->createWonRecord($alreadyWon, Prize::query()->create([
            'name' => '逐梦乐园奖',
            'level' => AwardLevels::DREAM_PARK,
            'stock' => 3,
            'status' => 'active',
        ]));

        $result = app(AwardSettlementService::class)->publishDreamParkQualifications();

        $this->assertSame(2, $result['count']);
        $this->assertDatabaseCount('lottery_qualifications', 2);
        foreach ([$eligible, $topWinner] as $user) {
            $this->assertDatabaseHas('lottery_qualifications', [
                'user_id' => $user->id,
                'source_type' => AwardLevels::DREAM_PARK,
                'qualified' => true,
                'chance_count' => 1,
                'used_count' => 0,
            ]);
        }
        foreach ([$missingWork, $alreadyWon] as $user) {
            $this->assertDatabaseMissing('lottery_qualifications', [
                'user_id' => $user->id,
                'source_type' => AwardLevels::DREAM_PARK,
            ]);
        }
    }

    private function createWork(
        User $user,
        string $type = WorkType::Traditional->value,
        string $group = WorkGroup::Employee->value,
        int $voteCount = 10,
        string $publishStatus = WorkPublishStatus::Published->value,
    ): Work {
        return Work::query()->create([
            'user_id' => $user->id,
            'type' => $type,
            'group' => $group,
            'title' => '作品 '.$user->employee_no,
            'description' => 'Demo',
            'publish_status' => $publishStatus,
            'vote_count' => $voteCount,
        ]);
    }

    private function createGameRecord(User $user): GameRecord
    {
        return GameRecord::query()->create([
            'user_id' => $user->id,
            'distance' => 1000,
            'score' => 5000,
            'duration' => 90,
            'played_at' => now(),
        ]);
    }

    private function createVotes(User $user, Work $work, int $count): void
    {
        for ($i = 0; $i < $count; $i++) {
            WorkVote::query()->create([
                'user_id' => $user->id,
                'work_id' => $work->id,
                'vote_date' => now()->subDays($i)->toDateString(),
                'source' => 'h5',
            ]);
        }
    }

    private function createWonRecord(User $user, Prize $prize): LotteryRecord
    {
        return LotteryRecord::query()->create([
            'user_id' => $user->id,
            'prize_id' => $prize->id,
            'source_type' => $prize->level,
            'result_status' => LotteryResultStatus::Won->value,
            'drawn_at' => now(),
        ]);
    }
}
