<?php

namespace App\Filament\Exports;

use App\Filament\Exports\Concerns\ExportsXlsxOnly;
use App\Models\OperationLog;
use App\Support\AdminDisplay;
use Filament\Actions\Exports\ExportColumn;
use Filament\Actions\Exports\Exporter;

class OperationLogExporter extends Exporter
{
    use ExportsXlsxOnly;

    protected static ?string $model = OperationLog::class;

    public static function getColumns(): array
    {
        return [
            ExportColumn::make('user.username')->label('操作人')
                ->formatStateUsing(fn (OperationLog $record): string => $record->user ? AdminDisplay::preferredName($record->user) : '系统'),
            ExportColumn::make('module')->label('模块'),
            ExportColumn::make('action')->label('动作'),
            ExportColumn::make('target_type')->label('对象类型'),
            ExportColumn::make('target_id')->label('内部对象编号'),
            ExportColumn::make('ip_address')->label('访问地址'),
            ExportColumn::make('created_at')->label('操作时间'),
        ];
    }
}
