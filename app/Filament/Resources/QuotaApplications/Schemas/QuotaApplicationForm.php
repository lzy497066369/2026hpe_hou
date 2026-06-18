<?php

namespace App\Filament\Resources\QuotaApplications\Schemas;

use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Schemas\Schema;

class QuotaApplicationForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('user_id')
                    ->label('员工')
                    ->relationship('user', 'name')
                    ->searchable(['name', 'employee_no', 'email'])
                    ->preload()
                    ->required()
                    ->disabled(fn (string $operation): bool => $operation === 'edit'),
                Select::make('audit_status')
                    ->label('审核状态')
                    ->options([
                        'submitted' => '已提交',
                        'under_review' => '审核中',
                        'approved' => '已通过',
                        'rejected' => '已驳回',
                    ])
                    ->required()
                    ->default('submitted'),
                Textarea::make('audit_remark')
                    ->label('审核备注')
                    ->columnSpanFull(),
                DateTimePicker::make('submitted_at')
                    ->label('提交时间'),
                DateTimePicker::make('reviewed_at')
                    ->label('审核时间'),
            ]);
    }
}
