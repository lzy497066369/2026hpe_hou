<?php

namespace App\Filament\Resources\LotteryRecords\Schemas;

use App\Support\AdminDisplay;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Schema;

class LotteryRecordInfolist
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
                    ->label('邮箱')
                    ->placeholder('-'),
                TextEntry::make('user.address')
                    ->label('城市')
                    ->placeholder('-'),
                TextEntry::make('user.work_address_code')
                    ->label('城市code')
                    ->placeholder('-'),
                TextEntry::make('prize.name')
                    ->label('奖品')
                    ->placeholder('未中奖'),
                TextEntry::make('work.title')
                    ->label('关联作品')
                    ->placeholder('-'),
                TextEntry::make('result_status')
                    ->label('中奖状态')
                    ->badge()
                    ->formatStateUsing(fn (?string $state): string => AdminDisplay::lotteryStatus($state)),
                TextEntry::make('prizeClaim.claim_status')
                    ->label('领奖状态')
                    ->badge()
                    ->formatStateUsing(fn (?string $state): string => AdminDisplay::claimStatus($state))
                    ->placeholder('-'),
                TextEntry::make('prizeClaim.claim_type')
                    ->label('奖品领取方式')
                    ->getStateUsing(fn ($record): string => AdminDisplay::claimType($record->prizeClaim?->claim_type)),
                TextEntry::make('prizeClaim.receiver_name')
                    ->label('姓名')
                    ->placeholder('-'),
                TextEntry::make('prizeClaim.receiver_phone')
                    ->label('电话')
                    ->placeholder('-'),
                TextEntry::make('prizeClaim.receiver_address')
                    ->label('地址')
                    ->placeholder('-'),
                TextEntry::make('drawn_at')
                    ->label('开奖时间')
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
