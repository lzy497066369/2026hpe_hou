<?php

namespace App\Services\Admin;

use App\Enums\LotteryResultStatus;
use App\Models\GameRecord;
use App\Models\LotteryRecord;
use App\Models\Prize;
use App\Models\User;
use App\Models\Work;
use Illuminate\Support\Facades\DB;

class FinalAwardService
{
    /**
     * @return array<string, int>
     */
    public function calculate(): array
    {
        return DB::transaction(function (): array {
            $talentPrize = Prize::query()->firstOrCreate(
                ['level' => 'talent_top'],
                ['name' => '赛博筑梦家才艺大赛奖', 'stock' => 60, 'status' => 'active']
            );
            $gamePrize = Prize::query()->firstOrCreate(
                ['level' => 'game_top'],
                ['name' => '线上小游戏奖/像素游戏王', 'stock' => 10, 'status' => 'active']
            );
            $participationPrize = Prize::query()->firstOrCreate(
                ['level' => 'participation'],
                ['name' => '阳光普照奖', 'stock' => 0, 'status' => 'active']
            );

            $talentUsers = Work::query()
                ->select('user_id')
                ->orderByDesc('vote_count')
                ->limit(60)
                ->pluck('user_id')
                ->unique();

            $gameUsers = GameRecord::query()
                ->select('user_id')
                ->orderByDesc('score')
                ->orderByDesc('distance')
                ->limit(10)
                ->pluck('user_id')
                ->unique();

            foreach ($talentUsers as $userId) {
                $this->award((int) $userId, $talentPrize->id);
            }

            foreach ($gameUsers as $userId) {
                $this->award((int) $userId, $gamePrize->id);
            }

            $alreadyAwarded = LotteryRecord::query()
                ->whereIn('prize_id', [$talentPrize->id, $gamePrize->id])
                ->pluck('user_id')
                ->unique();

            $participationCount = 0;
            User::query()
                ->whereNotIn('id', $alreadyAwarded)
                ->where(function ($query): void {
                    $query->whereHas('works')->orWhereHas('gameRecords');
                })
                ->each(function (User $user) use ($participationPrize, &$participationCount): void {
                    $this->award($user->id, $participationPrize->id);
                    $participationCount++;
                });

            return [
                'talentAwards' => $talentUsers->count(),
                'gameAwards' => $gameUsers->count(),
                'participationAwards' => $participationCount,
            ];
        });
    }

    private function award(int $userId, int $prizeId): void
    {
        LotteryRecord::query()->firstOrCreate(
            ['user_id' => $userId, 'prize_id' => $prizeId],
            ['result_status' => LotteryResultStatus::Won->value, 'drawn_at' => now()]
        );
    }
}
