<?php

namespace App\Filament\Resources\Exports\Tables;

use Filament\Actions\Action;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\ViewAction;
use Filament\Actions\Exports\Enums\ExportFormat;
use Filament\Actions\Exports\Models\Export;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\URL;

class ExportsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query): Builder => $query->with('user')->latest('id'))
            ->columns([
                TextColumn::make('id')
                    ->label('导出ID')
                    ->sortable(),
                TextColumn::make('file_name')
                    ->label('文件名')
                    ->searchable(),
                TextColumn::make('exporter')
                    ->label('导出器')
                    ->limit(36),
                TextColumn::make('user.name')
                    ->label('发起人')
                    ->searchable(),
                TextColumn::make('total_rows')
                    ->label('总行数')
                    ->numeric(),
                TextColumn::make('successful_rows')
                    ->label('成功行数')
                    ->numeric(),
                TextColumn::make('completed_at')
                    ->label('完成时间')
                    ->dateTime()
                    ->placeholder('处理中'),
                TextColumn::make('created_at')
                    ->label('创建时间')
                    ->dateTime()
                    ->sortable(),
            ])
            ->recordActions([
                Action::make('download')
                    ->label('下载')
                    ->url(fn (Export $record): string => URL::signedRoute(
                        'filament.exports.download',
                        [
                            'authGuard' => 'web',
                            'export' => $record,
                            'format' => ExportFormat::Csv,
                        ],
                        absolute: false,
                    ))
                    ->openUrlInNewTab()
                    ->visible(fn (Export $record): bool => $record->completed_at !== null),
                ViewAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
