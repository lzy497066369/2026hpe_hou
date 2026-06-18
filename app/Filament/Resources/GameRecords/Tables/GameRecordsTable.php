<?php

namespace App\Filament\Resources\GameRecords\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class GameRecordsTable
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
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
