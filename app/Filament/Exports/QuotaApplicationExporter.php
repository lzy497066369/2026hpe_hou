<?php

namespace App\Filament\Exports;

use App\Filament\Exports\Concerns\ExportsXlsxOnly;
use App\Models\QuotaApplication;
use App\Support\AdminDisplay;
use Filament\Actions\Exports\ExportColumn;
use Filament\Actions\Exports\Exporter;

class QuotaApplicationExporter extends Exporter
{
    use ExportsXlsxOnly;

    protected static ?string $model = QuotaApplication::class;

    public static function getColumns(): array
    {
        return [
            ExportColumn::make('user.name')->label('Preferred Name')
                ->formatStateUsing(fn (QuotaApplication $record): string => AdminDisplay::preferredName($record->user)),
            ExportColumn::make('user.email')->label('邮箱'),
            ExportColumn::make('employee_no')->label('员工号'),
            ExportColumn::make('material_file_ids')->label('材料文件')
                ->formatStateUsing(fn (QuotaApplication $record): string => $record->materialFilesForDisplay()
                    ->map(fn ($file): string => AdminDisplay::fileUrl($file) ?? AdminDisplay::fileName($file))
                    ->implode("\n")),
            ExportColumn::make('audit_status')->label('审核状态')
                ->formatStateUsing(fn (?string $state): string => AdminDisplay::auditStatus($state)),
            ExportColumn::make('audit_remark')->label('审核备注'),
            ExportColumn::make('submitted_at')->label('提交时间'),
            ExportColumn::make('reviewed_at')->label('审核时间'),
        ];
    }
}
