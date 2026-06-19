<?php

namespace App\Filament\Exports;

use App\Filament\Exports\Concerns\ExportsXlsxOnly;
use App\Models\User;
use App\Support\AdminDisplay;
use Filament\Actions\Exports\ExportColumn;
use Filament\Actions\Exports\Exporter;

class UsersExporter extends Exporter
{
    use ExportsXlsxOnly;

    protected static ?string $model = User::class;

    public static function getColumns(): array
    {
        return [
            ExportColumn::make('username')->label('Preferred Name')
                ->formatStateUsing(fn (User $record): string => AdminDisplay::preferredName($record)),
            ExportColumn::make('name')->label('姓名'),
            ExportColumn::make('email')->label('邮箱'),
            ExportColumn::make('employee_no')->label('员工号'),
            ExportColumn::make('nickname')->label('昵称'),
            ExportColumn::make('phone')->label('电话'),
            ExportColumn::make('address')->label('地址'),
            ExportColumn::make('work_city')->label('工作城市'),
            ExportColumn::make('mail_code')->label('邮箱代码'),
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
