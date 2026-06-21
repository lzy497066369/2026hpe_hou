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
        $reason = null;

        $query = LotteryQualification::query()
            ->where('user_id', $user->id)
            ->orderByDesc('chance_count');

        if ($sourceType !== null) {
            $query->where('source_type', $sourceType);
        }

        $qualification = $query->first();

        if ($qualification === null) {
            $reason = $this->qualificationMissingReason($sourceType);

            return [
                'sourceType' => $sourceType,
                'qualified' => false,
                'chanceCount' => 0,
                'usedCount' => 0,
                'reason' => $reason,
            ];
        }

        $hasDrawn = LotteryRecord::query()
            ->where('user_id', $user->id)
            ->where('source_type', $qualification->source_type)
            ->exists();
        $usedCount = $hasDrawn
            ? max($qualification->used_count, $qualification->chance_count)
            : $qualification->used_count;

        if ($qualification->qualified && $usedCount >= $qualification->chance_count) {
            $reason = 'chance_used_up';
        }

        if (! $qualification->qualified) {
            $reason = $usedCount >= $qualification->chance_count
                ? 'chance_used_up'
                : 'qualification_not_published';
        }

        return [
            'sourceType' => $qualification->source_type,
            'qualified' => $qualification->qualified,
            'chanceCount' => $qualification->chance_count,
            'usedCount' => $usedCount,
            'reason' => $reason,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function draw(User $user, ?string $sourceType = null): array
    {
        $sourceType = $this->normalizeSourceType($sourceType) ?? AwardLevels::FRAGRANCE_VOTE;

        $record = $this->lock->run("lottery:user:{$user->id}:{$sourceType}", function () use ($user, $sourceType): LotteryRecord {
            return DB::transaction(function () use ($user, $sourceType): LotteryRecord {
                $alreadyWon = LotteryRecord::query()
                    ->where('user_id', $user->id)
                    ->where('source_type', $sourceType)
                    ->where('result_status', LotteryResultStatus::Won->value)
                    ->lockForUpdate()
                    ->exists();

                abort_if($alreadyWon, 422, '该奖项已中奖');

                $alreadyDrawn = LotteryRecord::query()
                    ->where('user_id', $user->id)
                    ->where('source_type', $sourceType)
                    ->lockForUpdate()
                    ->exists();

                abort_if($alreadyDrawn, 422, '抽奖次数不足');

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

                $qualification->forceFill([
                    'used_count' => $qualification->chance_count,
                ])->save();

                $prize = Prize::query()
                    ->where('level', $sourceType)
                    ->where('status', 'active')
                    ->where('stock', '>', 0)
                    ->lockForUpdate()
                    ->first();

                if ($prize !== null) {
                    $prize->decrement('stock');
                }

                $record = LotteryRecord::query()->create([
                    'user_id' => $user->id,
                    'prize_id' => $prize?->id,
                    'source_type' => $sourceType,
                    'result_status' => $prize === null
                        ? LotteryResultStatus::Lost->value
                        : LotteryResultStatus::Won->value,
                    'drawn_at' => now(),
                ]);

                if ($prize !== null) {
                    $this->syncUserClaimPreferenceToRecord($user, $record);
                }

                return $record;
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
                'pickup_address' => $payload['pickupAddress'] ?? null,
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
            'pickupAddress' => $payload['pickupAddress'] ?? null,
            'pickupRemark' => $payload['pickupRemark'] ?? null,
        ];
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function myPrizes(User $user): array
    {
        $hasParticipationAward = false;

        return LotteryRecord::query()
            ->with(['prize', 'prizeClaim'])
            ->where('user_id', $user->id)
            ->whereNotNull('prize_id')
            ->orderByDesc('created_at')
            ->get()
            ->filter(function (LotteryRecord $record) use (&$hasParticipationAward): bool {
                $isParticipationAward = $record->source_type === AwardLevels::PARTICIPATION
                    || $record->prize?->level === AwardLevels::PARTICIPATION;

                if (! $isParticipationAward) {
                    return true;
                }

                if ($hasParticipationAward) {
                    return false;
                }

                $hasParticipationAward = true;

                return true;
            })
            ->values()
            ->map(function (LotteryRecord $record): array {
                $claim = $record->prizeClaim;

                return [
                    'id' => (string) $record->id,
                    'prize' => $record->prize === null ? null : [
                        'id' => (string) $record->prize->id,
                        'name' => $record->prize->name,
                        'level' => $record->prize->level,
                        'imageUrl' => $record->prize->image_url,
                    ],
                    'claimType' => $claim?->claim_type,
                    'claimStatus' => $claim?->claim_status,
                    'claimedAt' => $claim?->created_at?->toISOString(),
                    'receiverName' => $claim?->receiver_name,
                    'receiverPhone' => $claim?->receiver_phone,
                    'receiverAddress' => $claim?->receiver_address,
                    'pickupName' => $claim?->pickup_name,
                    'pickupPhone' => $claim?->pickup_phone,
                    'pickupEmployeeNo' => $claim?->pickup_employee_no,
                    'pickupAddress' => $claim?->pickup_address,
                    'pickupRemark' => $claim?->pickup_remark,
                ];
            })
            ->all();
    }

    /**
     * @return array<string, array<int, array<string, string>>>
     */
    public function announcements(): array
    {
        return [
            'fragrance' => $this->announcementRows(AwardLevels::FRAGRANCE_VOTE),
            'dream' => $this->announcementRows(AwardLevels::DREAM_PARK),
        ];
    }

    /**
     * @return array<int, array<string, string>>
     */
    private function announcementRows(string $level): array
    {
        return LotteryRecord::query()
            ->with(['user', 'prize'])
            ->where('result_status', LotteryResultStatus::Won->value)
            ->whereHas('prize', fn ($query) => $query->where('level', $level))
            ->orderBy('created_at')
            ->get()
            ->map(fn (LotteryRecord $record): array => [
                'id' => (string) $record->id,
                'nickname' => $record->user?->nickname ?: $record->user?->name ?: 'HPER',
                'employeeNo' => $this->maskEmployeeNo((string) ($record->user?->employee_no ?? '')),
            ])
            ->values()
            ->all();
    }

    private function maskEmployeeNo(string $employeeNo): string
    {
        if ($employeeNo === '') {
            return '';
        }

        $length = mb_strlen($employeeNo);
        if ($length <= 4) {
            return str_repeat('X', max($length, 1));
        }

        return str_repeat('X', $length - 4).mb_substr($employeeNo, -4);
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

    private function syncUserClaimPreferenceToRecord(User $user, LotteryRecord $record): void
    {
        if ($user->claim_type === null || $user->claim_type === '') {
            return;
        }

        PrizeClaim::query()->updateOrCreate(
            ['lottery_record_id' => $record->id],
            [
                'user_id' => $user->id,
                'claim_type' => $user->claim_type,
                'receiver_name' => $user->receiver_name ?: $user->name,
                'receiver_phone' => $user->receiver_phone ?: $user->phone,
                'receiver_address' => $user->receiver_address,
                'pickup_name' => $user->pickup_name ?: $user->name,
                'pickup_phone' => $user->pickup_phone ?: $user->phone,
                'pickup_employee_no' => $user->pickup_employee_no ?: $user->employee_no,
                'pickup_address' => $user->pickup_address,
                'pickup_remark' => $user->pickup_remark,
            ]
        );
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

    private function qualificationMissingReason(?string $sourceType): string
    {
        if ($sourceType === null) {
            return 'qualification_not_published';
        }

        $publishedExists = LotteryQualification::query()
            ->where('source_type', $sourceType)
            ->where('qualified', true)
            ->exists();

        return $publishedExists ? 'qualification_not_qualified' : 'qualification_not_published';
    }
}
