<?php

namespace App\Filament\Exports;

use App\Filament\Exports\Concerns\ExportsXlsxOnly;
use App\Models\GameRecord;
use App\Support\AdminDisplay;
use Filament\Actions\Exports\ExportColumn;
use Filament\Actions\Exports\Exporter;

class GameRecordExporter extends Exporter
{
    use ExportsXlsxOnly;

    protected static ?string $model = GameRecord::class;

    public static function getColumns(): array
    {
        return [
            ExportColumn::make('user_id')->label('内部用户编号'),
            ExportColumn::make('user.username')->label('Preferred Name')
                ->formatStateUsing(fn (GameRecord $record): string => AdminDisplay::preferredName($record->user)),
            ExportColumn::make('user.employee_no')->label('员工号'),
            ExportColumn::make('user.nickname')->label('昵称'),
            ExportColumn::make('distance')->label('距离'),
            ExportColumn::make('score')->label('分数'),
            ExportColumn::make('duration')->label('时长'),
            ExportColumn::make('played_at')->label('游戏时间'),
            ExportColumn::make('created_at')->label('创建时间'),
            ExportColumn::make('updated_at')->label('更新时间'),
        ];
    }
}
