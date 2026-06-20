<?php

namespace App\Filament\Exports;

use App\Filament\Exports\Concerns\ExportsXlsxOnly;
use App\Models\RegistrationProfile;
use App\Support\AdminDisplay;
use Filament\Actions\Exports\ExportColumn;
use Filament\Actions\Exports\Exporter;

class RegistrationProfileExporter extends Exporter
{
    use ExportsXlsxOnly;

    protected static ?string $model = RegistrationProfile::class;

    public static function getColumns(): array
    {
        return [
            ExportColumn::make('user.name')->label('Preferred Name')
                ->formatStateUsing(fn (RegistrationProfile $record): string => AdminDisplay::preferredName($record->user)),
            ExportColumn::make('user.email')->label('邮箱'),
            ExportColumn::make('employee_no')->label('员工号'),
            ExportColumn::make('name')->label('姓名'),
            ExportColumn::make('department')->label('部门'),
            ExportColumn::make('contact')->label('联系方式'),
            ExportColumn::make('material_file_ids')->label('材料文件')
                ->formatStateUsing(fn (RegistrationProfile $record): string => $record->materialFilesForDisplay()
                    ->map(fn ($file): string => AdminDisplay::fileUrl($file) ?? AdminDisplay::fileName($file))
                    ->implode("\n")),
            ExportColumn::make('audit_status')->label('审核状态')
                ->formatStateUsing(fn (?string $state): string => AdminDisplay::auditStatus($state)),
            ExportColumn::make('audit_remark')->label('审核备注'),
            ExportColumn::make('submitted_at')->label('提交时间'),
            ExportColumn::make('reviewed_at')->label('审核时间'),
            ExportColumn::make('created_at')->label('创建时间'),
            ExportColumn::make('updated_at')->label('更新时间'),
        ];
    }
}
