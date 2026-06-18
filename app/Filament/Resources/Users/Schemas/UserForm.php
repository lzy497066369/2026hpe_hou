<?php

namespace App\Filament\Resources\Users\Schemas;

use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class UserForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')
                    ->label('姓名')
                    ->required(),
                TextInput::make('email')
                    ->label('邮箱')
                    ->email()
                    ->required(),
                TextInput::make('employee_no')
                    ->label('员工号')
                    ->required(),
                TextInput::make('nickname')
                    ->label('昵称'),
                TextInput::make('phone')
                    ->label('电话')
                    ->tel(),
                TextInput::make('address')
                    ->label('地址')
                    ->columnSpanFull(),
                TextInput::make('avatar')
                    ->label('头像地址'),
                Select::make('status')
                    ->label('状态')
                    ->options([
                        'active' => '启用',
                        'disabled' => '禁用',
                    ])
                    ->required()
                    ->default('active'),
                Select::make('role')
                    ->label('角色')
                    ->options([
                        'user' => '活动用户',
                        'auditor' => '审核管理员',
                        'operator' => '运营管理员',
                        'admin' => '管理员',
                        'super_admin' => '超级管理员',
                    ])
                    ->required()
                    ->default('user'),
                DateTimePicker::make('email_verified_at')
                    ->label('邮箱验证时间'),
                TextInput::make('password')
                    ->label('密码')
                    ->password()
                    ->revealable()
                    ->required(fn (string $operation): bool => $operation === 'create')
                    ->dehydrated(fn (?string $state): bool => filled($state)),
            ]);
    }
}
