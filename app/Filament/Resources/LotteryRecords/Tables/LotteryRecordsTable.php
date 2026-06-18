<?php

namespace App\Filament\Resources\LotteryRecords\Tables;

use App\Services\Admin\FinalAwardService;
use App\Services\Admin\OperationLogger;
use App\Support\AdminDisplay;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class LotteryRecordsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('user_id')
                    ->label('内部用户编号')
                    ->toggleable(isToggledHiddenByDefault: true),
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
                TextColumn::make('prize.name')
                    ->label('奖品')
                    ->searchable(),
                TextColumn::make('result_status')
                    ->label('中奖状态')
                    ->badge()
                    ->formatStateUsing(fn (?string $state): string => AdminDisplay::lotteryStatus($state)),
                TextColumn::make('prizeClaim.claim_status')
                    ->label('领奖状态')
                    ->badge()
                    ->formatStateUsing(fn (?string $state): string => AdminDisplay::claimStatus($state))
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
            ->toolbarActions([
                Action::make('calculateFinalAwards')
                    ->label('计算最终奖项')
                    ->requiresConfirmation()
                    ->action(function (): void {
                        $result = app(FinalAwardService::class)->calculate();
                        app(OperationLogger::class)->log('lottery_records', 'calculate_final_awards', null, $result);
                    }),
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
