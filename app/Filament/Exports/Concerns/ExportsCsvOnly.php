<?php

namespace App\Filament\Exports\Concerns;

use Filament\Actions\Exports\Enums\ExportFormat;
use Filament\Actions\Exports\Models\Export;

trait ExportsCsvOnly
{
    public function getFormats(): array
    {
        return [ExportFormat::Csv];
    }

    public function getJobConnection(): string
    {
        return 'sync';
    }

    public static function getCompletedNotificationBody(Export $export): string
    {
        return '导出完成，共导出 '.$export->successful_rows.' 条记录。';
    }
}
