<?php

namespace App\Services\Admin;

use App\Models\GameRecord;
use App\Models\RegistrationProfile;
use App\Models\User;
use App\Models\Work;
use Illuminate\Support\Facades\DB;

class AdminStatisticsService
{
    /**
     * @return array<string, mixed>
     */
    public function overview(): array
    {
        return [
            'loginUserCount' => User::query()->count(),
            'workParticipantCount' => Work::query()->distinct('user_id')->count('user_id'),
            'workTotalCount' => Work::query()->count(),
            'workCountsByTrack' => Work::query()
                ->select('group', 'type', DB::raw('count(*) as count'))
                ->groupBy('group', 'type')
                ->get()
                ->map(fn ($row): array => [
                    'group' => $row->group,
                    'type' => $row->type,
                    'count' => (int) $row->count,
                ])
                ->all(),
            'registrationParticipantCount' => RegistrationProfile::query()->count(),
            'gameParticipantCount' => GameRecord::query()->distinct('user_id')->count('user_id'),
            'gamePlayTotalCount' => GameRecord::query()->count(),
        ];
    }
}
