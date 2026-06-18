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
                    ->label('员工姓名'),
                TextEntry::make('user.employee_no')
                    ->label('员工号'),
                TextEntry::make('user.email')
                    ->label('邮箱')
                    ->placeholder('-'),
                TextEntry::make('prize.name')
                    ->label('奖品')
                    ->placeholder('未中奖'),
                TextEntry::make('result_status')
                    ->label('中奖状态')
                    ->badge()
                    ->formatStateUsing(fn (?string $state): string => AdminDisplay::lotteryStatus($state)),
                TextEntry::make('prizeClaim.claim_status')
                    ->label('领奖状态')
                    ->badge()
                    ->formatStateUsing(fn (?string $state): string => AdminDisplay::claimStatus($state))
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
