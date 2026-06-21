<?php

namespace App\Filament\Exports;

use App\Filament\Exports\Concerns\ExportsCsvOnly;
use App\Models\Work;
use App\Support\AdminDisplay;
use Filament\Actions\Exports\ExportColumn;
use Filament\Actions\Exports\Exporter;

class WorkExporter extends Exporter
{
    use ExportsCsvOnly;

    protected static ?string $model = Work::class;

    public static function getColumns(): array
    {
        return [
            ExportColumn::make('id')->label('作品ID'),
            ExportColumn::make('user_id')->label('用户ID'),
            ExportColumn::make('cover_file_id')->label('封面文件ID'),
            ExportColumn::make('content_file_id')->label('内容文件ID'),
            ExportColumn::make('coverFile.url')->label('作品封面链接')
                ->formatStateUsing(fn (Work $record): ?string => AdminDisplay::fileUrl($record->coverFile)),
            ExportColumn::make('contentFile.url')->label('作品内容链接')
                ->formatStateUsing(fn (Work $record): ?string => AdminDisplay::fileUrl($record->contentFile)),
            ExportColumn::make('contentFile.mime_type')->label('作品内容类型'),
            ExportColumn::make('user.name')->label('Preferred Name')
                ->formatStateUsing(fn (Work $record): string => AdminDisplay::preferredName($record->user)),
            ExportColumn::make('user.employee_no')->label('员工号'),
            ExportColumn::make('user.email')->label('邮箱'),
            ExportColumn::make('user.city')->label('城市'),
            ExportColumn::make('user.work_address_code')->label('code（城市code）'),
            ExportColumn::make('user.nickname')->label('昵称'),
            ExportColumn::make('type')->label('类别')
                ->formatStateUsing(fn (?string $state): string => AdminDisplay::workType($state)),
            ExportColumn::make('group')->label('分组')
                ->formatStateUsing(fn (?string $state): string => AdminDisplay::workGroup($state)),
            ExportColumn::make('title')->label('作品标题'),
            ExportColumn::make('description')->label('作品描述'),
            ExportColumn::make('tool_name')->label('创作工具'),
            ExportColumn::make('prompt_text')->label('作品提示词'),
            ExportColumn::make('audit_status')->label('审核状态')
                ->formatStateUsing(fn (?string $state): string => AdminDisplay::auditStatus($state)),
            ExportColumn::make('publish_status')->label('发布状态')
                ->formatStateUsing(fn (?string $state): string => AdminDisplay::publishStatus($state)),
            ExportColumn::make('vote_count')->label('票数'),
            ExportColumn::make('submitted_at')->label('提交时间'),
            ExportColumn::make('reviewed_at')->label('审核时间'),
            ExportColumn::make('created_at')->label('创建时间'),
            ExportColumn::make('updated_at')->label('更新时间'),
        ];
    }
}
