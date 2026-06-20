<?php

namespace App\Filament\Exports;

use App\Filament\Exports\Concerns\ExportsCsvOnly;
use App\Models\Prize;
use App\Support\AdminDisplay;
use Filament\Actions\Exports\ExportColumn;
use Filament\Actions\Exports\Exporter;

class PrizeExporter extends Exporter
{
    use ExportsCsvOnly;

    protected static ?string $model = Prize::class;

    public static function getColumns(): array
    {
        return [
            ExportColumn::make('name')->label('奖品名称'),
            ExportColumn::make('level')->label('奖项标识'),
            ExportColumn::make('stock')->label('库存/名额'),
            ExportColumn::make('status')->label('状态')
                ->formatStateUsing(fn (?string $state): string => AdminDisplay::prizeStatus($state)),
            ExportColumn::make('image_url')->label('图片链接')
                ->formatStateUsing(fn (?string $state): ?string => AdminDisplay::url($state)),
            ExportColumn::make('created_at')->label('创建时间'),
            ExportColumn::make('updated_at')->label('更新时间'),
        ];
    }
}
