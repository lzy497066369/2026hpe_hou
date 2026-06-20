<?php

namespace App\Filament\Widgets;

use App\Services\Admin\AdminStatisticsService;
use Filament\Widgets\ChartWidget;

class GamePlayTrendChart extends ChartWidget
{
    protected ?string $heading = '游戏数据时间趋势';

    protected int|string|array $columnSpan = [
        'md' => 1,
        'xl' => 1,
    ];

    protected static ?int $sort = 20;

    protected function getData(): array
    {
        $trend = app(AdminStatisticsService::class)->overview()['gamePlayTrend'];

        return [
            'datasets' => [
                [
                    'label' => '游戏次数',
                    'data' => collect($trend)->pluck('count')->all(),
                    'borderColor' => '#006be8',
                    'backgroundColor' => 'rgba(0, 107, 232, 0.12)',
                    'fill' => true,
                    'tension' => 0.35,
                ],
            ],
            'labels' => collect($trend)->pluck('date')->all(),
        ];
    }

    protected function getType(): string
    {
        return 'line';
    }
}
