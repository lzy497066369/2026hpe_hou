<?php

namespace App\Services\Game;

use App\Models\GamePlayLog;
use App\Models\GameRecord;
use App\Models\User;
use App\Support\DatabaseColumn;
use Illuminate\Support\Facades\DB;

class GameService
{
    /**
     * @param array<string, mixed> $payload
     * @return array<string, mixed>
     */
    public function store(User $user, array $payload): array
    {
        $record = DB::transaction(function () use ($user, $payload): GameRecord {
            $playedAt = now();

            if (DatabaseColumn::tableExists('game_play_logs')) {
                GamePlayLog::query()->create([
                    'user_id' => $user->id,
                    'distance' => $payload['distance'],
                    'score' => $payload['score'],
                    'duration' => $payload['duration'],
                    'played_at' => $playedAt,
                ]);
            }

            $existing = GameRecord::query()
                ->where('user_id', $user->id)
                ->lockForUpdate()
                ->first();

            if ($existing === null) {
                return GameRecord::query()->create([
                    'user_id' => $user->id,
                    'distance' => $payload['distance'],
                    'score' => $payload['score'],
                    'duration' => $payload['duration'],
                    'played_at' => $playedAt,
                ]);
            }

            if ($this->isBetterRecord($payload, $existing)) {
                $existing->fill([
                    'distance' => $payload['distance'],
                    'score' => $payload['score'],
                    'duration' => $payload['duration'],
                    'played_at' => $playedAt,
                ])->save();
            }

            return $existing->fresh() ?? $existing;
        });

        return $this->formatRecord($record);
    }

    /**
     * @return array<string, mixed>
     */
    public function rankings(?User $user = null, int $limit = 30): array
    {
        $records = GameRecord::query()
            ->with('user')
            ->orderByDesc('score')
            ->orderByDesc('distance')
            ->limit($limit)
            ->get();

        $items = $records
            ->values()
            ->map(fn (GameRecord $record, int $index): array => [
                'rank' => $index + 1,
                'userId' => (string) $record->user_id,
                'nickname' => $record->user?->nickname,
                'employeeNo' => $record->user?->employee_no,
                'distance' => $record->distance,
                'score' => $record->score,
            ])
            ->all();

        $mine = null;
        if ($user !== null) {
            $mineRecord = GameRecord::query()
                ->where('user_id', $user->id)
                ->orderByDesc('score')
                ->orderByDesc('distance')
                ->first();
            $mine = $mineRecord === null ? null : $this->formatRecord($mineRecord);
        }

        return [
            'items' => $items,
            'mine' => $mine,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function formatRecord(GameRecord $record): array
    {
        $betterCount = GameRecord::query()
            ->where(function ($query) use ($record): void {
                $query->where('score', '>', $record->score)
                    ->orWhere(function ($query) use ($record): void {
                        $query->where('score', $record->score)
                            ->where('distance', '>', $record->distance);
                    });
            })
            ->count();

        return [
            'id' => (string) $record->id,
            'distance' => $record->distance,
            'score' => $record->score,
            'rank' => $betterCount + 1,
            'playedAt' => $record->played_at?->toISOString(),
        ];
    }

    /**
     * @param array<string, mixed> $payload
     */
    private function isBetterRecord(array $payload, GameRecord $record): bool
    {
        return (int) $payload['score'] > $record->score;
    }
}
