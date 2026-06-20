<?php

namespace App\Services\Admin;

use App\Enums\LotteryResultStatus;
use App\Enums\WorkGroup;
use App\Enums\WorkPublishStatus;
use App\Enums\WorkType;
use App\Models\GameRecord;
use App\Models\LotteryRecord;
use App\Models\RegistrationProfile;
use App\Models\User;
use App\Models\Work;
use App\Support\AwardLevels;
use Carbon\CarbonImmutable;
use Carbon\CarbonPeriod;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class AdminStatisticsService
{
    private const CACHE_KEY = 'admin:statistics:overview';
    private const CACHE_TTL_SECONDS = 60;

    /**
     * @return array<string, mixed>
     */
    public function overview(): array
    {
        return Cache::remember(self::CACHE_KEY, self::CACHE_TTL_SECONDS, fn (): array => $this->freshOverview());
    }

    public function forgetOverviewCache(): void
    {
        Cache::forget(self::CACHE_KEY);
    }

    /**
     * @return array<string, mixed>
     */
    private function freshOverview(): array
    {
        $today = CarbonImmutable::now('Asia/Shanghai');
        $workCountsByTrack = Work::query()
            ->select('group', 'type', DB::raw('count(*) as count'))
            ->groupBy('group', 'type')
            ->get()
            ->map(fn ($row): array => [
                'group' => $row->group,
                'type' => $row->type,
                'count' => (int) $row->count,
            ])
            ->all();

        return [
            'loginUserCount' => User::query()->count(),
            'workParticipantCount' => Work::query()->distinct('user_id')->count('user_id'),
            'workTotalCount' => Work::query()->count(),
            'workCountsByTrack' => $workCountsByTrack,
            'todayGamePlayCount' => $this->countToday(GameRecord::query(), 'played_at', $today),
            'todayWorkUploadCount' => $this->countToday(Work::query(), 'created_at', $today),
            'todayLoginUserCount' => $this->lastLoginAtExists()
                ? $this->countToday(User::query(), 'last_login_at', $today)
                : 0,
            'claimableParticipationAwardCount' => $this->claimableParticipationAwardCount(),
            'traditionalEmployeeWorkCount' => $this->workCountFor(WorkType::Traditional->value, WorkGroup::Employee->value),
            'traditionalChildrenWorkCount' => $this->workCountFor(WorkType::Traditional->value, WorkGroup::Children->value),
            'aiEmployeeWorkCount' => $this->workCountFor(WorkType::Ai->value, WorkGroup::Employee->value),
            'aiChildrenWorkCount' => $this->workCountFor(WorkType::Ai->value, WorkGroup::Children->value),
            'gamePlayTrend' => $this->dailyTrend(GameRecord::query(), 'played_at', $today),
            'workUploadTrend' => $this->dailyTrend(Work::query(), 'created_at', $today),
            'registrationParticipantCount' => RegistrationProfile::query()->count(),
            'gameParticipantCount' => GameRecord::query()->distinct('user_id')->count('user_id'),
            'gamePlayTotalCount' => GameRecord::query()->count(),
        ];
    }

    private function countToday($query, string $column, CarbonImmutable $today): int
    {
        return (int) $query
            ->whereBetween($column, [$today->startOfDay(), $today->endOfDay()])
            ->count();
    }

    private function workCountFor(string $type, string $group): int
    {
        return (int) Work::query()
            ->where('type', $type)
            ->where('group', $group)
            ->count();
    }

    /**
     * @return array<int, array{date: string, count: int}>
     */
    private function dailyTrend($query, string $column, CarbonImmutable $today, int $days = 14): array
    {
        $start = $today->subDays($days - 1)->startOfDay();
        $end = $today->endOfDay();
        $counts = $query
            ->select(DB::raw($this->dateExpression($column).' as day'), DB::raw('count(*) as count'))
            ->whereBetween($column, [$start, $end])
            ->groupBy('day')
            ->pluck('count', 'day');

        return collect(CarbonPeriod::create($start, '1 day', $end))
            ->map(fn ($date): array => [
                'date' => $date->toDateString(),
                'count' => (int) ($counts[$date->toDateString()] ?? 0),
            ])
            ->values()
            ->all();
    }

    private function dateExpression(string $column): string
    {
        return DB::connection()->getDriverName() === 'sqlite'
            ? "strftime('%Y-%m-%d', {$column})"
            : "date({$column})";
    }

    private function claimableParticipationAwardCount(): int
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

        return (int) Work::query()
            ->where('publish_status', WorkPublishStatus::Published->value)
            ->whereNotIn('user_id', $excludedUserIds)
            ->whereHas('user.gameRecords')
            ->count();
    }

    private function lastLoginAtExists(): bool
    {
        return Schema::hasColumn('users', 'last_login_at');
    }
}
