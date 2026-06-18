<?php

namespace App\Filament\Resources\RegistrationProfiles\Schemas;

use App\Models\UploadedFile;
use App\Support\AdminDisplay;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Schemas\Schema;

class RegistrationProfileForm
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
                TextInput::make('employee_no')
                    ->label('员工号')
                    ->required(),
                TextInput::make('name')
                    ->label('姓名')
                    ->required(),
                TextInput::make('department')
                    ->label('部门')
                    ->required(),
                TextInput::make('contact')
                    ->label('联系方式')
                    ->required(),
                Select::make('material_file_id')
                    ->label('材料文件')
                    ->relationship('materialFile', 'url')
                    ->getOptionLabelFromRecordUsing(fn (UploadedFile $record): string => AdminDisplay::fileName($record))
                    ->searchable(['url', 'path', 'mime_type'])
                    ->preload(),
                Select::make('audit_status')
                    ->label('审核状态')
                    ->options([
                        'draft' => '草稿',
                        'submitted' => '已提交',
                        'under_review' => '审核中',
                        'approved' => '已通过',
                        'rejected' => '已驳回',
                    ])
                    ->required()
                    ->default('draft'),
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
