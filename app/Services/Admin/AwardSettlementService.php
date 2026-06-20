<?php

namespace App\Services\Admin;

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
use App\Support\AwardLevels;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class AwardSettlementService
{
    /**
     * @return array<int, array<string, mixed>>
     */
    public function previewTalentAwards(): array
    {
        return collect($this->talentTracks())
            ->flatMap(fn (array $track): Collection => $this->talentWinnersForTrack($track['type'], $track['group'], $track['label']))
            ->values()
            ->all();
    }

    /**
     * @return array<string, mixed>
     */
    public function awardTalentAwards(): array
    {
        $prize = $this->prize(AwardLevels::TALENT_TOP, '赛博筑梦家才艺大赛奖', 60);
        $rows = $this->previewTalentAwards();

        return $this->awardRows($rows, $prize);
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function previewGameAwards(): array
    {
        $rankedRecords = GameRecord::query()
            ->orderByDesc('score')
            ->orderBy('id')
            ->limit(10)
            ->get();

        if ($rankedRecords->isEmpty()) {
            return [];
        }

        $threshold = $rankedRecords->last()->score;

        return GameRecord::query()
            ->with('user')
            ->where('score', '>=', $threshold)
            ->orderByDesc('score')
            ->orderBy('id')
            ->get()
            ->map(fn (GameRecord $record): array => [
                ...$this->userColumns($record->user),
                'score' => $record->score,
                'distance' => $record->distance,
            ])
            ->values()
            ->all();
    }

    /**
     * @return array<string, mixed>
     */
    public function awardGameAwards(): array
    {
        $prize = $this->prize(AwardLevels::GAME_TOP, '线上小游戏奖/像素游戏王', 10);

        return $this->awardRows($this->previewGameAwards(), $prize);
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function previewParticipationAwards(): array
    {
        $excludedUserIds = LotteryRecord::query()
            ->where('result_status', LotteryResultStatus::Won->value)
            ->whereHas('prize', fn ($query) => $query->whereIn('level', [
                AwardLevels::TALENT_TOP,
                AwardLevels::GAME_TOP,
            ]))
            ->pluck('user_id')
            ->unique()
            ->all();

        return Work::query()
            ->with('user')
            ->where('publish_status', WorkPublishStatus::Published->value)
            ->whereNotIn('user_id', $excludedUserIds)
            ->whereHas('user.gameRecords')
            ->orderBy('id')
            ->get()
            ->map(fn (Work $work): array => [
                ...$this->userColumns($work->user),
                'work_id' => $work->id,
                'work_title' => $work->title,
            ])
            ->values()
            ->all();
    }

    /**
     * @return array<string, mixed>
     */
    public function awardParticipationAwards(): array
    {
        $prize = $this->prize(AwardLevels::PARTICIPATION, '阳光普照奖', 0);

        return $this->awardRows($this->previewParticipationAwards(), $prize);
    }

    /**
     * @return array<string, int>
     */
    public function awardAllFixedAwards(): array
    {
        return DB::transaction(function (): array {
            $talent = $this->awardTalentAwards();
            $game = $this->awardGameAwards();
            $participation = $this->awardParticipationAwards();

            return [
                'talentAwards' => $talent['count'],
                'gameAwards' => $game['count'],
                'participationAwards' => $participation['count'],
            ];
        });
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function fragranceWinners(): array
    {
        return $this->winnersForPrizeLevel(AwardLevels::FRAGRANCE_VOTE);
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function dreamParkWinners(): array
    {
        return $this->winnersForPrizeLevel(AwardLevels::DREAM_PARK);
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function fragranceCandidates(): array
    {
        $voteCounts = WorkVote::query()
            ->select('user_id', DB::raw('count(*) as votes_count'))
            ->groupBy('user_id')
            ->pluck('votes_count', 'user_id');

        $winnerIds = $this->winnerIdsForLevel(AwardLevels::FRAGRANCE_VOTE);

        return User::query()
            ->orderBy('employee_no')
            ->orderBy('id')
            ->get()
            ->map(function (User $user) use ($voteCounts, $winnerIds): array {
                $weight = (int) ($voteCounts[$user->id] ?? 0);
                $reasons = [];

                if ($weight <= 0) {
                    $reasons[] = '未为他人投票';
                }

                if (in_array($user->id, $winnerIds, true)) {
                    $reasons[] = '已获得手有余香奖';
                }

                return [
                    ...$this->userColumns($user),
                    'weight' => $weight,
                    'eligible' => $reasons === [],
                    'reason' => $reasons === [] ? null : implode('；', $reasons),
                ];
            })
            ->values()
            ->all();
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function previewFragranceQualifications(): array
    {
        return collect($this->fragranceCandidates())
            ->filter(fn (array $row): bool => $row['eligible'])
            ->map(fn (array $row): array => [
                ...$row,
                'chance_count' => $row['weight'],
            ])
            ->values()
            ->all();
    }

    /**
     * @return array<string, mixed>
     */
    public function publishFragranceQualifications(): array
    {
        return $this->publishQualifications(
            AwardLevels::FRAGRANCE_VOTE,
            $this->previewFragranceQualifications(),
            fn (array $row): int => (int) $row['chance_count'],
        );
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function dreamParkCandidates(): array
    {
        $usersWithPublishedWork = Work::query()
            ->where('publish_status', WorkPublishStatus::Published->value)
            ->pluck('user_id')
            ->unique()
            ->all();
        $usersWithGame = GameRecord::query()->pluck('user_id')->unique()->all();
        $usersWithVote = WorkVote::query()->pluck('user_id')->unique()->all();
        $winnerIds = $this->winnerIdsForLevel(AwardLevels::DREAM_PARK);

        return User::query()
            ->orderBy('employee_no')
            ->orderBy('id')
            ->get()
            ->map(function (User $user) use ($usersWithPublishedWork, $usersWithGame, $usersWithVote, $winnerIds): array {
                $reasons = [];

                if (! in_array($user->id, $usersWithPublishedWork, true)) {
                    $reasons[] = '未发布作品';
                }

                if (! in_array($user->id, $usersWithGame, true)) {
                    $reasons[] = '未玩游戏';
                }

                if (! in_array($user->id, $usersWithVote, true)) {
                    $reasons[] = '未为他人投票';
                }

                if (in_array($user->id, $winnerIds, true)) {
                    $reasons[] = '已获得逐梦乐园奖';
                }

                return [
                    ...$this->userColumns($user),
                    'eligible' => $reasons === [],
                    'reason' => $reasons === [] ? null : implode('；', $reasons),
                ];
            })
            ->values()
            ->all();
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function previewDreamParkQualifications(): array
    {
        return collect($this->dreamParkCandidates())
            ->filter(fn (array $row): bool => $row['eligible'])
            ->map(fn (array $row): array => [
                ...$row,
                'chance_count' => 1,
            ])
            ->values()
            ->all();
    }

    /**
     * @return array<string, mixed>
     */
    public function publishDreamParkQualifications(): array
    {
        return $this->publishQualifications(
            AwardLevels::DREAM_PARK,
            $this->previewDreamParkQualifications(),
            fn (): int => 1,
        );
    }

    /**
     * @return array<int, array{type: string, group: string, label: string}>
     */
    private function talentTracks(): array
    {
        return [
            ['type' => WorkType::Traditional->value, 'group' => WorkGroup::Employee->value, 'label' => '传统创作-员工组'],
            ['type' => WorkType::Traditional->value, 'group' => WorkGroup::Children->value, 'label' => '传统创作-儿童组'],
            ['type' => WorkType::Ai->value, 'group' => WorkGroup::Employee->value, 'label' => 'AI 创作-员工组'],
            ['type' => WorkType::Ai->value, 'group' => WorkGroup::Children->value, 'label' => 'AI 创作-儿童组'],
        ];
    }

    private function talentWinnersForTrack(string $type, string $group, string $label): Collection
    {
        $rankedWorks = Work::query()
            ->where('type', $type)
            ->where('group', $group)
            ->where('publish_status', WorkPublishStatus::Published->value)
            ->orderByDesc('vote_count')
            ->orderBy('id')
            ->limit(15)
            ->get();

        if ($rankedWorks->isEmpty()) {
            return collect();
        }

        $threshold = $rankedWorks->last()->vote_count;

        return Work::query()
            ->with('user')
            ->where('type', $type)
            ->where('group', $group)
            ->where('publish_status', WorkPublishStatus::Published->value)
            ->where('vote_count', '>=', $threshold)
            ->orderByDesc('vote_count')
            ->orderBy('id')
            ->get()
            ->map(fn (Work $work): array => [
                ...$this->userColumns($work->user),
                'track' => $label,
                'work_id' => $work->id,
                'work_title' => $work->title,
                'vote_count' => $work->vote_count,
            ]);
    }

    private function prize(string $level, string $name, int $stock): Prize
    {
        return Prize::query()->firstOrCreate(
            ['level' => $level],
            ['name' => $name, 'stock' => $stock, 'status' => 'active']
        );
    }

    /**
     * @param array<int, array<string, mixed>> $rows
     * @return array<string, mixed>
     */
    private function awardRows(array $rows, Prize $prize): array
    {
        $count = 0;

        foreach ($rows as $row) {
            $attributes = isset($row['work_id'])
                ? ['prize_id' => $prize->id, 'work_id' => $row['work_id']]
                : ['user_id' => $row['user_id'], 'prize_id' => $prize->id];

            LotteryRecord::query()->firstOrCreate(
                $attributes,
                [
                    'user_id' => $row['user_id'],
                    'work_id' => $row['work_id'] ?? null,
                    'source_type' => $prize->level,
                    'result_status' => LotteryResultStatus::Won->value,
                    'drawn_at' => now(),
                ]
            );

            $count++;
        }

        return [
            'count' => $count,
            'rows' => $rows,
        ];
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function winnersForPrizeLevel(string $level): array
    {
        return LotteryRecord::query()
            ->with(['user', 'prize'])
            ->where('result_status', LotteryResultStatus::Won->value)
            ->whereHas('prize', fn ($query) => $query->where('level', $level))
            ->orderBy('id')
            ->get()
            ->map(fn (LotteryRecord $record): array => [
                ...$this->userColumns($record->user),
                'prize_name' => $record->prize?->name,
                'drawn_at' => $record->drawn_at?->format('Y-m-d H:i:s'),
            ])
            ->values()
            ->all();
    }

    /**
     * @return array<int, int>
     */
    private function winnerIdsForLevel(string $level): array
    {
        return LotteryRecord::query()
            ->where('result_status', LotteryResultStatus::Won->value)
            ->whereHas('prize', fn ($query) => $query->where('level', $level))
            ->pluck('user_id')
            ->unique()
            ->values()
            ->all();
    }

    /**
     * @return array<string, mixed>
     */
    private function userColumns(?User $user): array
    {
        return [
            'user_id' => $user?->id,
            'name' => $user?->name,
            'employee_no' => $user?->employee_no,
            'email' => $user?->email,
            'nickname' => $user?->nickname,
        ];
    }

    /**
     * @param array<int, array<string, mixed>> $rows
     * @param callable(array<string, mixed>): int $chanceResolver
     * @return array<string, mixed>
     */
    private function publishQualifications(string $sourceType, array $rows, callable $chanceResolver): array
    {
        return DB::transaction(function () use ($sourceType, $rows, $chanceResolver): array {
            foreach ($rows as $row) {
                $qualification = LotteryQualification::query()->firstOrNew([
                    'user_id' => $row['user_id'],
                    'source_type' => $sourceType,
                ]);

                if (! $qualification->exists) {
                    $qualification->used_count = 0;
                }

                $qualification->qualified = true;
                $qualification->chance_count = $chanceResolver($row);
                $qualification->save();
            }

            return [
                'count' => count($rows),
                'rows' => $rows,
            ];
        });
    }
}
