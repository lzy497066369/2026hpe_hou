<?php

namespace App\Filament\Resources\LotteryRecords\Tables;

use App\Filament\Exports\LotteryRecordExporter;
use App\Services\Admin\FinalAwardService;
use App\Services\Admin\OperationLogger;
use App\Support\AdminDisplay;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ExportAction;
use Filament\Actions\ExportBulkAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class LotteryRecordsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query): Builder => $query->with(['prize', 'prizeClaim', 'user', 'work']))
            ->columns([
                TextColumn::make('user_id')
                    ->label('内部用户编号')
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('user.name')
                    ->label('Preferred Name')
                    ->getStateUsing(fn ($record): string => AdminDisplay::preferredName($record->user))
                    ->searchable(),
                TextColumn::make('user.employee_no')
                    ->label('员工号')
                    ->searchable(),
                TextColumn::make('user.email')
                    ->label('邮箱')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('user.address')
                    ->label('城市')
                    ->searchable()
                    ->placeholder('-'),
                TextColumn::make('user.work_address_code')
                    ->label('城市code')
                    ->searchable()
                    ->placeholder('-'),
                TextColumn::make('prize.name')
                    ->label('奖品')
                    ->searchable(),
                TextColumn::make('work.title')
                    ->label('关联作品')
                    ->searchable()
                    ->placeholder('-'),
                TextColumn::make('result_status')
                    ->label('中奖状态')
                    ->badge()
                    ->formatStateUsing(fn (?string $state): string => AdminDisplay::lotteryStatus($state)),
                TextColumn::make('prizeClaim.claim_status')
                    ->label('领奖状态')
                    ->badge()
                    ->formatStateUsing(fn (?string $state): string => AdminDisplay::claimStatus($state))
                    ->placeholder('-'),
                TextColumn::make('prizeClaim.claim_type')
                    ->label('奖品领取方式')
                    ->getStateUsing(fn ($record): string => AdminDisplay::claimType($record->prizeClaim?->claim_type)),
                TextColumn::make('prizeClaim.receiver_name')
                    ->label('姓名')
                    ->placeholder('-'),
                TextColumn::make('prizeClaim.receiver_phone')
                    ->label('电话')
                    ->placeholder('-'),
                TextColumn::make('prizeClaim.receiver_address')
                    ->label('地址')
                    ->limit(32)
                    ->placeholder('-'),
                TextColumn::make('drawn_at')
                    ->label('开奖时间')
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
                SelectFilter::make('result_status')
                    ->label('中奖状态')
                    ->options([
                        'pending' => '待开奖',
                        'won' => '已中奖',
                        'missed' => '未中奖',
                    ]),
            ])
            ->recordActions([
                ViewAction::make(),
                EditAction::make(),
            ])
            ->headerActions([
                ExportAction::make()
                    ->label('导出 CSV')
                    ->exporter(LotteryRecordExporter::class),
            ])
            ->toolbarActions([
                Action::make('calculateFinalAwards')
                    ->label('计算最终奖项')
                    ->requiresConfirmation()
                    ->action(function (): void {
                        $result = app(FinalAwardService::class)->calculate();
                        app(OperationLogger::class)->log('lottery_records', 'calculate_final_awards', null, $result);
                    }),
                BulkActionGroup::make([
                    ExportBulkAction::make()
                        ->label('导出选中 CSV')
                        ->exporter(LotteryRecordExporter::class),
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
