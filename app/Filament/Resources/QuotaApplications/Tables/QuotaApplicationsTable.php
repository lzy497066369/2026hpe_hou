<?php

namespace App\Filament\Resources\QuotaApplications\Tables;

use App\Filament\Exports\QuotaApplicationExporter;
use App\Models\QuotaApplication;
use App\Services\Admin\OperationLogger;
use App\Support\AdminDisplay;
use Filament\Actions\Action;
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
use Illuminate\Database\Eloquent\Builder;

class QuotaApplicationsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query): Builder => $query->with('user'))
            ->defaultSort('submitted_at', 'desc')
            ->columns([
                TextColumn::make('user.name')
                    ->label('Preferred Name')
                    ->getStateUsing(fn (QuotaApplication $record): string => AdminDisplay::preferredName($record->user))
                    ->searchable(),
                TextColumn::make('user.email')
                    ->label('邮箱')
                    ->searchable(),
                TextColumn::make('employee_no')
                    ->label('员工号')
                    ->searchable(),
                ImageColumn::make('material_images')
                    ->label('材料图片')
                    ->getStateUsing(fn (QuotaApplication $record): array => $record->materialImageUrls())
                    ->imageHeight(64)
                    ->stacked()
                    ->limit(2)
                    ->limitedRemainingText()
                    ->url(fn (?string $state): ?string => $state, true)
                    ->placeholder('未上传'),
                TextColumn::make('audit_status')
                    ->label('审核状态')
                    ->badge()
                    ->formatStateUsing(fn (?string $state): string => AdminDisplay::auditStatus($state)),
                TextColumn::make('audit_remark')
                    ->label('审核备注')
                    ->limit(24)
                    ->searchable(),
                TextColumn::make('submitted_at')
                    ->label('提交时间')
                    ->dateTime()
                    ->sortable(),
                TextColumn::make('reviewed_at')
                    ->label('审核时间')
                    ->dateTime()
                    ->sortable(),
            ])
            ->filters([
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
                    ->visible(fn (QuotaApplication $record): bool => $record->audit_status !== 'approved')
                    ->action(function (QuotaApplication $record): void {
                        $record->update([
                            'audit_status' => 'approved',
                            'reviewed_at' => now(),
                        ]);

                        app(OperationLogger::class)->log('quota_applications', 'approve', $record);
                    }),
                Action::make('reject')
                    ->label('驳回')
                    ->requiresConfirmation()
                    ->visible(fn (QuotaApplication $record): bool => $record->audit_status !== 'rejected')
                    ->action(function (QuotaApplication $record): void {
                        $record->update([
                            'audit_status' => 'rejected',
                            'reviewed_at' => now(),
                        ]);

                        app(OperationLogger::class)->log('quota_applications', 'reject', $record);
                    }),
                ViewAction::make(),
                EditAction::make(),
            ])
            ->headerActions([
                ExportAction::make()
                    ->label('导出 Excel')
                    ->exporter(QuotaApplicationExporter::class),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    ExportBulkAction::make()
                        ->label('导出选中 Excel')
                        ->exporter(QuotaApplicationExporter::class),
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
