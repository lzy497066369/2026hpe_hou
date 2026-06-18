<?php

namespace App\Filament\Resources\OperationLogs\Tables;

use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class OperationLogsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('user.name')
                    ->label('操作人')
                    ->placeholder('系统')
                    ->searchable(),
                TextColumn::make('module')
                    ->label('模块')
                    ->formatStateUsing(fn (?string $state): string => [
                        'works' => '作品管理',
                        'registration_profiles' => '材料审核',
                        'lottery_records' => '获奖记录',
                    ][$state] ?? '系统运营')
                    ->searchable(),
                TextColumn::make('action')
                    ->label('动作')
                    ->badge()
                    ->formatStateUsing(fn (?string $state): string => [
                        'approve' => '通过',
                        'reject' => '驳回',
                        'adjust_votes' => '调票',
                        'calculate_final_awards' => '计算最终奖项',
                    ][$state] ?? '系统操作')
                    ->searchable(),
                TextColumn::make('target_type')
                    ->label('对象类型')
                    ->limit(32)
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('target_id')
                    ->label('内部对象编号')
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('ip_address')
                    ->label('访问地址')
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('created_at')
                    ->label('操作时间')
                    ->dateTime()
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('module')
                    ->label('模块')
                    ->options([
                        'works' => '作品',
                        'registration_profiles' => '材料审核',
                        'lottery_records' => '获奖记录',
                    ]),
            ])
            ->recordActions([
                ViewAction::make(),
            ]);
    }
}
