<?php

namespace App\Services\Lottery;

use App\Enums\LotteryResultStatus;
use App\Models\LotteryQualification;
use App\Models\LotteryRecord;
use App\Models\Prize;
use App\Models\PrizeClaim;
use App\Models\User;
use App\Services\Support\AtomicLock;
use Illuminate\Support\Facades\DB;

class LotteryService
{
    public function __construct(private readonly AtomicLock $lock)
    {
    }

    /**
     * @return array<string, mixed>
     */
    public function qualification(User $user): array
    {
        $qualification = LotteryQualification::query()
            ->where('user_id', $user->id)
            ->orderByDesc('chance_count')
            ->first();

        if ($qualification === null) {
            return [
                'qualified' => false,
                'chanceCount' => 0,
                'usedCount' => 0,
                'reason' => 'lottery_not_open',
            ];
        }

        return [
            'qualified' => $qualification->qualified,
            'chanceCount' => $qualification->chance_count,
            'usedCount' => $qualification->used_count,
            'reason' => $qualification->qualified ? null : 'chance_used_up',
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function draw(User $user): array
    {
        $record = $this->lock->run("lottery:user:{$user->id}", function () use ($user): LotteryRecord {
            return DB::transaction(function () use ($user): LotteryRecord {
                $qualification = LotteryQualification::query()
                    ->where('user_id', $user->id)
                    ->where('qualified', true)
                    ->lockForUpdate()
                    ->first();

                abort_if(
                    $qualification !== null && $qualification->used_count >= $qualification->chance_count,
                    422,
                    '抽奖次数不足'
                );

                if ($qualification !== null) {
                    $qualification->increment('used_count');
                }

                $prize = Prize::query()
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
            'prize' => $record->prize === null ? null : [
                'id' => (string) $record->prize->id,
                'name' => $record->prize->name,
                'level' => $record->prize->level,
                'imageUrl' => $record->prize->image_url,
            ],
            'drawnAt' => $record->drawn_at?->toISOString(),
        ];
    }
}
