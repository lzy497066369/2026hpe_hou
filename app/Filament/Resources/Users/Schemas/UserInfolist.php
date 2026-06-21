<?php

namespace App\Filament\Resources\Users\Schemas;

use App\Support\AdminDisplay;
use Filament\Infolists\Components\ImageEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Schema;

class UserInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextEntry::make('name')
                    ->label('Preferred Name')
                    ->getStateUsing(fn ($record): string => AdminDisplay::preferredName($record)),
                TextEntry::make('email')
                    ->label('邮箱'),
                TextEntry::make('employee_no')
                    ->label('员工号'),
                TextEntry::make('nickname')
                    ->label('昵称')
                    ->placeholder('-'),
                TextEntry::make('phone')
                    ->label('电话')
                    ->placeholder('-'),
                TextEntry::make('city')
                    ->label('工作城市')
                    ->placeholder('-'),
                TextEntry::make('work_address_code')
                    ->label('工作地址代码')
                    ->placeholder('-'),
                TextEntry::make('work_quota_display')
                    ->label('已上传/可上传')
                    ->getStateUsing(fn ($record): string => $record->workQuotaDisplay()),
                TextEntry::make('quota_application_summary')
                    ->label('更多名额申请')
                    ->getStateUsing(fn ($record): string => $record->quotaApplicationSummary()),
                TextEntry::make('latestQuotaApplication.audit_status')
                    ->label('最近申请状态')
                    ->badge()
                    ->formatStateUsing(fn (?string $state): string => $state === null ? '未申请' : AdminDisplay::auditStatus($state)),
                TextEntry::make('latestQuotaApplication.submitted_at')
                    ->label('最近申请时间')
                    ->dateTime()
                    ->placeholder('-'),
                ImageEntry::make('avatar')
                    ->label('头像')
                    ->getStateUsing(fn ($record): ?string => AdminDisplay::url($record->avatar))
                    ->imageHeight(96)
                    ->placeholder('-'),
                TextEntry::make('status')
                    ->label('状态')
                    ->badge()
                    ->formatStateUsing(fn (?string $state): string => AdminDisplay::userStatus($state)),
                TextEntry::make('role')
                    ->label('角色')
                    ->badge()
                    ->formatStateUsing(fn (?string $state): string => AdminDisplay::userRole($state)),
                TextEntry::make('email_verified_at')
                    ->label('邮箱验证时间')
                    ->dateTime()
                    ->placeholder('-'),
                TextEntry::make('created_at')
                    ->label('创建时间')
                    ->dateTime()
                    ->placeholder('-'),
                TextEntry::make('updated_at')
                    ->label('更新时间')
                    ->dateTime()
                    ->placeholder('-'),
            ]);
    }
}
