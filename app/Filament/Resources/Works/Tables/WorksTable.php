<?php

namespace App\Filament\Resources\Works\Tables;

use App\Models\Work;
use App\Services\Admin\OperationLogger;
use App\Support\AdminDisplay;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\TextInput;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class WorksTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('submitted_at', 'desc')
            ->columns([
                ImageColumn::make('coverFile.url')
                    ->label('作品封面')
                    ->getStateUsing(fn (Work $record): ?string => AdminDisplay::fileUrl($record->coverFile))
                    ->imageHeight(64),
                TextColumn::make('user.name')
                    ->label('姓名')
                    ->searchable(),
                TextColumn::make('user.employee_no')
                    ->label('员工号')
                    ->searchable(),
                TextColumn::make('user.email')
                    ->label('邮箱')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('user.nickname')
                    ->label('昵称')
                    ->searchable(),
                TextColumn::make('type')
                    ->label('类别')
                    ->badge()
                    ->formatStateUsing(fn (?string $state): string => AdminDisplay::workType($state)),
                TextColumn::make('group')
                    ->label('分组')
                    ->badge()
                    ->formatStateUsing(fn (?string $state): string => AdminDisplay::workGroup($state)),
                TextColumn::make('title')
                    ->label('作品标题')
                    ->searchable(),
                TextColumn::make('tool_name')
                    ->label('创作工具')
                    ->searchable(),
                TextColumn::make('audit_status')
                    ->label('审核状态')
                    ->badge()
                    ->formatStateUsing(fn (?string $state): string => AdminDisplay::auditStatus($state)),
                TextColumn::make('publish_status')
                    ->label('发布状态')
                    ->badge()
                    ->formatStateUsing(fn (?string $state): string => AdminDisplay::publishStatus($state)),
                TextColumn::make('vote_count')
                    ->label('票数')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('submitted_at')
                    ->label('提交时间')
                    ->dateTime()
                    ->sortable(),
                TextColumn::make('reviewed_at')
                    ->label('审核时间')
                    ->dateTime()
                    ->sortable(),
                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('group')
                    ->label('作品分组')
                    ->options([
                        'children' => '儿童组',
                        'employee' => '员工组',
                    ]),
                SelectFilter::make('type')
                    ->label('作品类别')
                    ->options([
                        'traditional' => '传统创作',
                        'ai' => 'AI 创作',
                    ]),
                SelectFilter::make('audit_status')
                    ->label('审核状态')
                    ->options([
                        'submitted' => '已提交',
                        'under_review' => '审核中',
                        'approved' => '已通过',
                        'rejected' => '已驳回',
                    ]),
            ])
            ->recordActions([
                Action::make('approve')
                    ->label('通过')
                    ->requiresConfirmation()
                    ->visible(fn (Work $record): bool => $record->audit_status !== 'approved')
                    ->action(function (Work $record): void {
                        $record->update([
                            'audit_status' => 'approved',
                            'publish_status' => 'published',
                            'reviewed_at' => now(),
                        ]);

                        app(OperationLogger::class)->log('works', 'approve', $record);
                    }),
                Action::make('reject')
                    ->label('驳回')
                    ->requiresConfirmation()
                    ->visible(fn (Work $record): bool => $record->audit_status !== 'rejected')
                    ->action(function (Work $record): void {
                        $record->update([
                            'audit_status' => 'rejected',
                            'publish_status' => 'hidden',
                            'reviewed_at' => now(),
                        ]);

                        app(OperationLogger::class)->log('works', 'reject', $record);
                    }),
                Action::make('adjustVotes')
                    ->label('调票')
                    ->schema([
                        TextInput::make('vote_count')
                            ->label('调整后票数')
                            ->numeric()
                            ->required()
                            ->minValue(0),
                    ])
                    ->action(function (Work $record, array $data): void {
                        $before = $record->vote_count;
                        $record->update(['vote_count' => (int) $data['vote_count']]);

                        app(OperationLogger::class)->log('works', 'adjust_votes', $record, [
                            'before' => $before,
                            'after' => (int) $data['vote_count'],
                        ]);
                    }),
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
