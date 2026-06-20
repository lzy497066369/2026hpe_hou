<?php

namespace App\Filament\Resources\ActivitySettings\Tables;

use App\Filament\Exports\ActivitySettingExporter;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ExportAction;
use Filament\Actions\ExportBulkAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class ActivitySettingsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('key')
                    ->label('配置键')
                    ->searchable(),
                TextColumn::make('label')
                    ->label('配置名称')
                    ->searchable(),
                TextColumn::make('type')
                    ->label('类型')
                    ->badge(),
                TextColumn::make('value')
                    ->label('配置值')
                    ->limit(48)
                    ->searchable(),
                TextColumn::make('description')
                    ->label('说明')
                    ->limit(32),
                TextColumn::make('updated_at')
                    ->label('更新时间')
                    ->dateTime()
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('type')
                    ->label('类型')
                    ->options([
                        'string' => '文本',
                        'boolean' => '开关',
                        'datetime' => '时间',
                        'number' => '数字',
                    ]),
            ])
            ->recordActions([
                ViewAction::make(),
                EditAction::make(),
            ])
            ->headerActions([
                ExportAction::make()
                    ->label('导出 CSV')
                    ->exporter(ActivitySettingExporter::class),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    ExportBulkAction::make()
                        ->label('导出选中 CSV')
                        ->exporter(ActivitySettingExporter::class),
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
