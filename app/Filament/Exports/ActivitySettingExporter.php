<?php

namespace App\Filament\Exports;

use App\Filament\Exports\Concerns\ExportsXlsxOnly;
use App\Models\ActivitySetting;
use Filament\Actions\Exports\ExportColumn;
use Filament\Actions\Exports\Exporter;

class ActivitySettingExporter extends Exporter
{
    use ExportsXlsxOnly;

    protected static ?string $model = ActivitySetting::class;

    public static function getColumns(): array
    {
        return [
            ExportColumn::make('key')->label('配置键'),
            ExportColumn::make('label')->label('配置名称'),
            ExportColumn::make('type')->label('类型'),
            ExportColumn::make('value')->label('配置值'),
            ExportColumn::make('description')->label('说明'),
            ExportColumn::make('updated_at')->label('更新时间'),
        ];
    }
}
