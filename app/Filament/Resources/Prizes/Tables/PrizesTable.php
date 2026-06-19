<?php

namespace App\Filament\Resources\Prizes\Tables;

use App\Filament\Exports\PrizeExporter;
use App\Support\AdminDisplay;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ExportAction;
use Filament\Actions\ExportBulkAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class PrizesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->label('奖品名称')
                    ->searchable(),
                TextColumn::make('level')
                    ->label('奖项标识')
                    ->searchable(),
                TextColumn::make('stock')
                    ->label('库存/名额')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('status')
                    ->label('状态')
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => [
                        'active' => '启用',
                        'disabled' => '禁用',
                    ][$state] ?? $state),
                ImageColumn::make('image_url')
                    ->label('图片')
                    ->getStateUsing(fn ($record): ?string => AdminDisplay::url($record->image_url)),
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
                SelectFilter::make('status')
                    ->label('状态')
                    ->options([
                        'active' => '启用',
                        'disabled' => '禁用',
                    ]),
            ])
            ->recordActions([
                ViewAction::make(),
                EditAction::make(),
            ])
            ->headerActions([
                ExportAction::make()
                    ->label('导出 Excel')
                    ->exporter(PrizeExporter::class),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    ExportBulkAction::make()
                        ->label('导出选中 Excel')
                        ->exporter(PrizeExporter::class),
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
