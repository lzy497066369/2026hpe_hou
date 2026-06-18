<?php

namespace App\Filament\Resources\Users\Tables;

use App\Enums\RegistrationAuditStatus;
use App\Support\AdminDisplay;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Illuminate\Database\Eloquent\Builder;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class UsersTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query): Builder => $query
                ->with('latestQuotaApplication')
                ->withCount([
                    'works',
                    'quotaApplications as submitted_quota_applications_count' => fn (Builder $query): Builder => $query
                        ->where('audit_status', RegistrationAuditStatus::Submitted->value),
                    'quotaApplications as approved_quota_applications_count' => fn (Builder $query): Builder => $query
                        ->where('audit_status', RegistrationAuditStatus::Approved->value),
                    'quotaApplications as rejected_quota_applications_count' => fn (Builder $query): Builder => $query
                        ->where('audit_status', RegistrationAuditStatus::Rejected->value),
                ]))
            ->columns([
                TextColumn::make('name')
                    ->label('姓名')
                    ->searchable(),
                TextColumn::make('email')
                    ->label('邮箱')
                    ->searchable(),
                TextColumn::make('employee_no')
                    ->label('员工号')
                    ->searchable(),
                TextColumn::make('nickname')
                    ->label('昵称')
                    ->searchable(),
                TextColumn::make('phone')
                    ->label('电话')
                    ->searchable(),
                TextColumn::make('address')
                    ->label('地址')
                    ->limit(24)
                    ->searchable(),
                TextColumn::make('work_quota_display')
                    ->label('已上传/可上传')
                    ->getStateUsing(fn ($record): string => $record->workQuotaDisplay()),
                TextColumn::make('quota_application_summary')
                    ->label('更多名额申请')
                    ->getStateUsing(fn ($record): string => $record->quotaApplicationSummary()),
                TextColumn::make('latestQuotaApplication.audit_status')
                    ->label('最近申请状态')
                    ->badge()
                    ->formatStateUsing(fn (?string $state): string => $state === null ? '未申请' : AdminDisplay::auditStatus($state)),
                TextColumn::make('status')
                    ->label('状态')
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => [
                        'active' => '启用',
                        'disabled' => '禁用',
                    ][$state] ?? $state),
                TextColumn::make('role')
                    ->label('角色')
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => [
                        'user' => '活动用户',
                        'auditor' => '审核管理员',
                        'operator' => '运营管理员',
                        'admin' => '管理员',
                        'super_admin' => '超级管理员',
                    ][$state] ?? $state),
                TextColumn::make('created_at')
                    ->label('创建时间')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('updated_at')
                    ->label('更新时间')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->label('状态')
                    ->options([
                        'active' => '启用',
                        'disabled' => '禁用',
                    ]),
                SelectFilter::make('role')
                    ->label('角色')
                    ->options([
                        'user' => '活动用户',
                        'auditor' => '审核管理员',
                        'operator' => '运营管理员',
                        'admin' => '管理员',
                        'super_admin' => '超级管理员',
                    ]),
            ])
            ->recordActions([
                ViewAction::make(),
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
