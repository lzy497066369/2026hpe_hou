<?php

namespace App\Filament\Resources\GameRecords\Tables;

use App\Filament\Exports\GameRecordExporter;
use App\Models\GameRecord;
use App\Services\Admin\OperationLogger;
use App\Support\AdminDisplay;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ExportAction;
use Filament\Actions\ExportBulkAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\TextInput;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class GameRecordsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query): Builder => $query->with('user'))
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
                TextColumn::make('user.nickname')
                    ->label('昵称')
                    ->searchable(),
                TextColumn::make('distance')
                    ->label('距离')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('score')
                    ->label('分数')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('duration')
                    ->label('时长')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('played_at')
                    ->label('游戏时间')
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
                //
            ])
            ->recordActions([
                Action::make('adjustScore')
                    ->label('改成绩')
                    ->modalHeading('修改游戏成绩')
                    ->schema([
                        TextInput::make('score')
                            ->label('成绩')
                            ->numeric()
                            ->required()
                            ->minValue(0),
                    ])
                    ->action(function (GameRecord $record, array $data): void {
                        $before = $record->score;
                        $record->update([
                            'score' => (int) $data['score'],
                        ]);

                        app(OperationLogger::class)->log('game_records', 'adjust_score', $record, [
                            'before' => $before,
                            'after' => (int) $data['score'],
                        ]);
                    }),
                ViewAction::make(),
                EditAction::make(),
            ])
            ->headerActions([
                ExportAction::make()
                    ->label('导出 CSV')
                    ->exporter(GameRecordExporter::class),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    ExportBulkAction::make()
                        ->label('导出选中 CSV')
                        ->exporter(GameRecordExporter::class),
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
