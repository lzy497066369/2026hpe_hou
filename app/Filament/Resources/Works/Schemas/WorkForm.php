<?php

namespace App\Filament\Resources\Works\Schemas;

use App\Models\UploadedFile;
use App\Support\AdminDisplay;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Schemas\Schema;

class WorkForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('user_id')
                    ->label('员工')
                    ->relationship('user', 'name')
                    ->searchable(['name', 'employee_no', 'email'])
                    ->required()
                    ->disabled(fn (string $operation): bool => $operation === 'edit'),
                Select::make('type')
                    ->label('作品类别')
                    ->options([
                        'traditional' => '传统创作',
                        'ai' => 'AI 创作',
                    ])
                    ->required(),
                Select::make('group')
                    ->label('作品分组')
                    ->options([
                        'children' => '儿童组',
                        'employee' => '员工组',
                    ])
                    ->required(),
                TextInput::make('title')
                    ->label('作品标题')
                    ->required(),
                Textarea::make('description')
                    ->label('作品描述')
                    ->columnSpanFull(),
                Select::make('cover_file_id')
                    ->label('作品封面')
                    ->relationship('coverFile', 'url')
                    ->getOptionLabelFromRecordUsing(fn (UploadedFile $record): string => AdminDisplay::fileName($record))
                    ->searchable(['url', 'path', 'mime_type']),
                Select::make('content_file_id')
                    ->label('作品文件')
                    ->relationship('contentFile', 'url')
                    ->getOptionLabelFromRecordUsing(fn (UploadedFile $record): string => AdminDisplay::fileName($record))
                    ->searchable(['url', 'path', 'mime_type']),
                TextInput::make('tool_name')
                    ->label('创作工具'),
                Textarea::make('prompt_text')
                    ->label('作品提示词')
                    ->columnSpanFull(),
                Select::make('audit_status')
                    ->label('审核状态')
                    ->options([
                        'draft' => '草稿',
                        'submitted' => '已提交',
                        'under_review' => '审核中',
                        'approved' => '已通过',
                        'rejected' => '已驳回',
                        'published' => '已发布',
                    ])
                    ->required()
                    ->default('submitted'),
                Select::make('publish_status')
                    ->label('发布状态')
                    ->options([
                        'hidden' => '隐藏',
                        'published' => '展示',
                    ])
                    ->required()
                    ->default('hidden'),
                TextInput::make('vote_count')
                    ->label('票数')
                    ->required()
                    ->numeric()
                    ->default(0),
                DateTimePicker::make('submitted_at')
                    ->label('提交时间'),
                DateTimePicker::make('reviewed_at')
                    ->label('审核时间'),
            ]);
    }
}
