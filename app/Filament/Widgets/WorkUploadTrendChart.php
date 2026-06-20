<?php

namespace App\Filament\Widgets;

use App\Services\Admin\AdminStatisticsService;
use Filament\Widgets\ChartWidget;

class WorkUploadTrendChart extends ChartWidget
{
    protected ?string $heading = '作品上传时间趋势';

    protected int|string|array $columnSpan = [
        'md' => 1,
        'xl' => 1,
    ];

    protected static ?int $sort = 30;

    protected function getData(): array
    {
        $trend = app(AdminStatisticsService::class)->overview()['workUploadTrend'];

        return [
            'datasets' => [
                [
                    'label' => '上传作品数量',
                    'data' => collect($trend)->pluck('count')->all(),
                    'borderColor' => '#16a34a',
                    'backgroundColor' => 'rgba(22, 163, 74, 0.12)',
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
