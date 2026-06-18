<?php

namespace App\Services\Lottery;

use App\Enums\LotteryResultStatus;
use App\Models\LotteryQualification;
use App\Models\LotteryRecord;
use App\Models\Prize;
use App\Models\PrizeClaim;
use App\Models\User;
use App\Services\Support\AtomicLock;
use App\Support\AwardLevels;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;

class LotteryService
{
    public function __construct(private readonly AtomicLock $lock)
    {
    }

    /**
     * @return array<string, mixed>
     */
    public function qualification(User $user, ?string $sourceType = null): array
    {
        $sourceType = $this->normalizeSourceType($sourceType);

        $query = LotteryQualification::query()
            ->where('user_id', $user->id)
            ->orderByDesc('chance_count');

        if ($sourceType !== null) {
            $query->where('source_type', $sourceType);
        }

        $qualification = $query->first();

        if ($qualification === null) {
            return [
                'sourceType' => $sourceType,
                'qualified' => false,
                'chanceCount' => 0,
                'usedCount' => 0,
                'reason' => 'lottery_not_open',
            ];
        }

        return [
            'sourceType' => $qualification->source_type,
            'qualified' => $qualification->qualified,
            'chanceCount' => $qualification->chance_count,
            'usedCount' => $qualification->used_count,
            'reason' => $qualification->qualified ? null : 'chance_used_up',
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function draw(User $user, ?string $sourceType = null): array
    {
        $sourceType = $this->normalizeSourceType($sourceType) ?? AwardLevels::FRAGRANCE_VOTE;
        $this->ensureOfficialPoolIsOpen($sourceType);

        $record = $this->lock->run("lottery:user:{$user->id}:{$sourceType}", function () use ($user, $sourceType): LotteryRecord {
            return DB::transaction(function () use ($user, $sourceType): LotteryRecord {
                $alreadyWon = LotteryRecord::query()
                    ->where('user_id', $user->id)
                    ->where('source_type', $sourceType)
                    ->where('result_status', LotteryResultStatus::Won->value)
                    ->lockForUpdate()
                    ->exists();

                abort_if($alreadyWon, 422, '该奖项已中奖');

                $qualification = LotteryQualification::query()
                    ->where('user_id', $user->id)
                    ->where('source_type', $sourceType)
                    ->where('qualified', true)
                    ->lockForUpdate()
                    ->first();

                abort_if(
                    $qualification === null || $qualification->used_count >= $qualification->chance_count,
                    422,
                    '抽奖次数不足'
                );

                $qualification->increment('used_count');

                $prize = Prize::query()
                    ->where('level', $sourceType)
                    ->where('status', 'active')
                    ->where('stock', '>', 0)
                    ->lockForUpdate()
                    ->first();

                if ($prize !== null) {
                    $prize->decrement('stock');
                }

                return LotteryRecord::query()->create([
                    'user_id' => $user->id,
                    'prize_id' => $prize?->id,
                    'source_type' => $sourceType,
                    'result_status' => $prize === null
                        ? LotteryResultStatus::Lost->value
                        : LotteryResultStatus::Won->value,
                    'drawn_at' => now(),
                ]);
            });
        });

        return $this->formatRecord($record);
    }

    /**
     * @param array<string, mixed> $payload
     * @return array<string, mixed>
     */
    public function claim(User $user, string $recordId, array $payload): array
    {
        $record = LotteryRecord::query()
            ->where('id', $recordId)
            ->where('user_id', $user->id)
            ->firstOrFail();

        PrizeClaim::query()->updateOrCreate(
            ['lottery_record_id' => $record->id],
            [
                'user_id' => $user->id,
                'claim_type' => $payload['claimType'],
                'receiver_name' => $payload['receiverName'],
                'receiver_phone' => $payload['receiverPhone'],
                'receiver_address' => $payload['receiverAddress'] ?? null,
                'pickup_name' => $payload['pickupName'] ?? null,
                'pickup_phone' => $payload['pickupPhone'] ?? null,
                'pickup_employee_no' => $payload['pickupEmployeeNo'] ?? null,
                'pickup_remark' => $payload['pickupRemark'] ?? null,
            ]
        );

        return [
            'claimType' => $payload['claimType'],
            'receiverName' => $payload['receiverName'],
            'receiverPhone' => $payload['receiverPhone'],
            'receiverAddress' => $payload['receiverAddress'] ?? null,
            'pickupName' => $payload['pickupName'] ?? null,
            'pickupPhone' => $payload['pickupPhone'] ?? null,
            'pickupEmployeeNo' => $payload['pickupEmployeeNo'] ?? null,
            'pickupRemark' => $payload['pickupRemark'] ?? null,
        ];
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function myPrizes(User $user): array
    {
        return LotteryRecord::query()
            ->with(['prize', 'prizeClaim'])
            ->where('user_id', $user->id)
            ->whereNotNull('prize_id')
            ->orderByDesc('created_at')
            ->get()
            ->map(function (LotteryRecord $record): array {
                return [
                    'id' => (string) $record->id,
                    'prize' => $record->prize === null ? null : [
                        'id' => (string) $record->prize->id,
                        'name' => $record->prize->name,
                        'level' => $record->prize->level,
                        'imageUrl' => $record->prize->image_url,
                    ],
                    'claimType' => $record->prizeClaim?->claim_type,
                    'claimStatus' => $record->prizeClaim?->claim_status,
                    'claimedAt' => $record->prizeClaim?->created_at?->toISOString(),
                ];
            })
            ->all();
    }

    /**
     * @return array<string, mixed>
     */
    private function formatRecord(LotteryRecord $record): array
    {
        $record->load('prize');

        return [
            'id' => (string) $record->id,
            'resultStatus' => $record->result_status,
            'sourceType' => $record->source_type,
            'prize' => $record->prize === null ? null : [
                'id' => (string) $record->prize->id,
                'name' => $record->prize->name,
                'level' => $record->prize->level,
                'imageUrl' => $record->prize->image_url,
            ],
            'drawnAt' => $record->drawn_at?->toISOString(),
        ];
    }

    private function normalizeSourceType(?string $sourceType): ?string
    {
        if ($sourceType === null || $sourceType === '') {
            return null;
        }

        abort_unless(
            in_array($sourceType, [
                AwardLevels::FRAGRANCE_VOTE,
                AwardLevels::DREAM_PARK,
                'contract-test',
                'manual',
            ], true),
            422,
            '抽奖类型不正确'
        );

        return $sourceType;
    }

    private function ensureOfficialPoolIsOpen(string $sourceType): void
    {
        if (! in_array($sourceType, [AwardLevels::FRAGRANCE_VOTE, AwardLevels::DREAM_PARK], true)) {
            return;
        }

        $openAt = CarbonImmutable::parse('2026-07-10 09:00:00', 'Asia/Shanghai');

        abort_if(now('Asia/Shanghai')->lt($openAt), 422, '抽奖尚未开始');
    }
}
