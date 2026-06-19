<?php

namespace App\Filament\Resources\GameRecords\Tables;

use App\Filament\Exports\GameRecordExporter;
use App\Support\AdminDisplay;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ExportAction;
use Filament\Actions\ExportBulkAction;
use Filament\Actions\ViewAction;
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
                TextColumn::make('user.username')
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
                ViewAction::make(),
                EditAction::make(),
            ])
            ->headerActions([
                ExportAction::make()
                    ->label('导出 Excel')
                    ->exporter(GameRecordExporter::class),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    ExportBulkAction::make()
                        ->label('导出选中 Excel')
                        ->exporter(GameRecordExporter::class),
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
