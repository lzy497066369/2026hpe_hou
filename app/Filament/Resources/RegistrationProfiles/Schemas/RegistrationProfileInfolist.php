<?php

namespace App\Filament\Resources\RegistrationProfiles\Schemas;

use App\Support\AdminDisplay;
use Filament\Infolists\Components\ImageEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Schema;

class RegistrationProfileInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextEntry::make('user.name')
                    ->label('Preferred Name')
                    ->getStateUsing(fn ($record): string => AdminDisplay::preferredName($record->user)),
                TextEntry::make('user.email')
                    ->label('邮箱')
                    ->placeholder('-'),
                TextEntry::make('employee_no')
                    ->label('员工号'),
                TextEntry::make('name')
                    ->label('姓名'),
                TextEntry::make('department')
                    ->label('部门'),
                TextEntry::make('contact')
                    ->label('联系方式'),
                ImageEntry::make('material_images')
                    ->label('材料图片')
                    ->getStateUsing(fn ($record): array => $record->materialImageUrls())
                    ->imageHeight(220)
                    ->wrap()
                    ->url(fn (?string $state): ?string => $state, true)
                    ->placeholder('未上传')
                    ->columnSpanFull(),
                TextEntry::make('audit_status')
                    ->label('审核状态')
                    ->badge()
                    ->formatStateUsing(fn (?string $state): string => AdminDisplay::auditStatus($state)),
                TextEntry::make('audit_remark')
                    ->label('审核备注')
                    ->placeholder('-'),
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
