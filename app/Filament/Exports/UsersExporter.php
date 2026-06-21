<?php

namespace App\Filament\Exports;

use App\Filament\Exports\Concerns\ExportsCsvOnly;
use App\Models\User;
use App\Support\AdminDisplay;
use Filament\Actions\Exports\ExportColumn;
use Filament\Actions\Exports\Exporter;

class UsersExporter extends Exporter
{
    use ExportsCsvOnly;

    protected static ?string $model = User::class;

    public static function getColumns(): array
    {
        return [
            ExportColumn::make('name')->label('Preferred Name')
                ->formatStateUsing(fn (User $record): string => AdminDisplay::preferredName($record)),
            ExportColumn::make('email')->label('邮箱'),
            ExportColumn::make('employee_no')->label('员工号'),
            ExportColumn::make('nickname')->label('昵称'),
            ExportColumn::make('phone')->label('电话'),
            ExportColumn::make('city')->label('工作城市'),
            ExportColumn::make('work_address_code')->label('工作地址代码'),
            ExportColumn::make('status')->label('状态')
                ->formatStateUsing(fn (?string $state): string => AdminDisplay::userStatus($state)),
            ExportColumn::make('role')->label('角色')
                ->formatStateUsing(fn (?string $state): string => AdminDisplay::userRole($state)),
            ExportColumn::make('created_at')->label('创建时间'),
            ExportColumn::make('updated_at')->label('更新时间'),
        ];
    }
}
