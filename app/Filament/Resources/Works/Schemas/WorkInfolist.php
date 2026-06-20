<?php

namespace App\Filament\Resources\Works\Schemas;

use App\Support\AdminDisplay;
use Filament\Infolists\Components\ImageEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Components\ViewEntry;
use Filament\Schemas\Schema;

class WorkInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextEntry::make('user.name')
                    ->label('Preferred Name')
                    ->getStateUsing(fn ($record): string => AdminDisplay::preferredName($record->user)),
                TextEntry::make('user.employee_no')
                    ->label('员工号'),
                TextEntry::make('user.email')
                    ->label('邮箱'),
                TextEntry::make('type')
                    ->label('作品类别')
                    ->formatStateUsing(fn (?string $state): string => AdminDisplay::workType($state)),
                TextEntry::make('group')
                    ->label('作品分组')
                    ->formatStateUsing(fn (?string $state): string => AdminDisplay::workGroup($state)),
                TextEntry::make('title')
                    ->label('作品标题'),
                TextEntry::make('description')
                    ->label('作品描述')
                    ->placeholder('-')
                    ->columnSpanFull(),
                ImageEntry::make('coverFile.url')
                    ->label('作品封面')
                    ->getStateUsing(fn ($record): ?string => AdminDisplay::fileUrl($record->coverFile))
                    ->imageHeight(220)
                    ->placeholder('未上传')
                    ->columnSpanFull(),
                TextEntry::make('contentFile.url')
                    ->label('作品文件')
                    ->getStateUsing(fn ($record): string => AdminDisplay::fileName($record->contentFile))
                    ->url(fn ($record): ?string => AdminDisplay::fileUrl($record->contentFile), true)
                    ->placeholder('未上传'),
                ViewEntry::make('content_preview')
                    ->label('作品内容预览')
                    ->view('filament.infolists.work-media-preview')
                    ->columnSpanFull(),
                TextEntry::make('tool_name')
                    ->label('创作工具')
                    ->placeholder('-'),
                TextEntry::make('prompt_text')
                    ->label('作品提示词')
                    ->placeholder('-')
                    ->columnSpanFull(),
                TextEntry::make('audit_status')
                    ->label('审核状态')
                    ->badge()
                    ->formatStateUsing(fn (?string $state): string => AdminDisplay::auditStatus($state)),
                TextEntry::make('publish_status')
                    ->label('发布状态')
                    ->badge()
                    ->formatStateUsing(fn (?string $state): string => AdminDisplay::publishStatus($state)),
                TextEntry::make('vote_count')
                    ->label('票数')
                    ->numeric(),
                TextEntry::make('submitted_at')
                    ->label('提交时间')
                    ->dateTime()
                    ->placeholder('-'),
                TextEntry::make('reviewed_at')
                    ->label('审核时间')
                    ->dateTime()
                    ->placeholder('-'),
                TextEntry::make('created_at')
                    ->label('创建时间')
                    ->dateTime()
                    ->placeholder('-'),
                TextEntry::make('updated_at')
                    ->label('更新时间')
                    ->dateTime()
                    ->placeholder('-'),
            ]);
    }
}
