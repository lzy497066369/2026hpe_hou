<?php

namespace App\Filament\Widgets;

use App\Services\Admin\AdminStatisticsService;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class AdminOverviewStats extends StatsOverviewWidget
{
    protected function getStats(): array
    {
        $overview = app(AdminStatisticsService::class)->overview();

        return [
            Stat::make('登录人数', (string) $overview['loginUserCount']),
            Stat::make('资料填写人数', (string) $overview['registrationParticipantCount']),
            Stat::make('上传作品人数', (string) $overview['workParticipantCount']),
            Stat::make('作品总数', (string) $overview['workTotalCount']),
            Stat::make('游戏参与人数', (string) $overview['gameParticipantCount']),
            Stat::make('游戏总次数', (string) $overview['gamePlayTotalCount']),
        ];
    }
}
