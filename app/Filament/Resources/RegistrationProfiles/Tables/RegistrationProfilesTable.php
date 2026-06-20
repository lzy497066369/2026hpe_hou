<?php

namespace App\Filament\Resources\RegistrationProfiles\Tables;

use App\Filament\Exports\RegistrationProfileExporter;
use App\Models\RegistrationProfile;
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

class RegistrationProfilesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query): Builder => $query->with('user'))
            ->defaultSort('submitted_at', 'desc')
            ->columns([
                TextColumn::make('user.name')
                    ->label('Preferred Name')
                    ->getStateUsing(fn (RegistrationProfile $record): string => AdminDisplay::preferredName($record->user))
                    ->searchable(),
                TextColumn::make('user.email')
                    ->label('邮箱')
                    ->searchable(),
                TextColumn::make('employee_no')
                    ->label('员工号')
                    ->searchable(),
                TextColumn::make('name')
                    ->label('姓名')
                    ->searchable(),
                TextColumn::make('department')
                    ->label('部门')
                    ->searchable(),
                TextColumn::make('contact')
                    ->label('联系方式')
                    ->searchable(),
                ImageColumn::make('material_images')
                    ->label('材料图片')
                    ->getStateUsing(fn (RegistrationProfile $record): array => $record->materialImageUrls())
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
                    ->visible(fn (RegistrationProfile $record): bool => $record->audit_status !== 'approved')
                    ->action(function (RegistrationProfile $record): void {
                        $record->update([
                            'audit_status' => 'approved',
                            'reviewed_at' => now(),
                        ]);

                        app(OperationLogger::class)->log('registration_profiles', 'approve', $record);
                    }),
                Action::make('reject')
                    ->label('驳回')
                    ->requiresConfirmation()
                    ->visible(fn (RegistrationProfile $record): bool => $record->audit_status !== 'rejected')
                    ->action(function (RegistrationProfile $record): void {
                        $record->update([
                            'audit_status' => 'rejected',
                            'reviewed_at' => now(),
                        ]);

                        app(OperationLogger::class)->log('registration_profiles', 'reject', $record);
                    }),
                ViewAction::make(),
                EditAction::make(),
            ])
            ->headerActions([
                ExportAction::make()
                    ->label('导出 CSV')
                    ->exporter(RegistrationProfileExporter::class),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    ExportBulkAction::make()
                        ->label('导出选中 CSV')
                        ->exporter(RegistrationProfileExporter::class),
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
