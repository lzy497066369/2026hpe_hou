<?php

namespace App\Filament\Resources\GameRecords\Schemas;

use App\Support\AdminDisplay;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Schema;

class GameRecordInfolist
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
                TextEntry::make('user.nickname')
                    ->label('昵称')
                    ->placeholder('-'),
                TextEntry::make('distance')
                    ->label('距离')
                    ->numeric(),
                TextEntry::make('score')
                    ->label('分数')
                    ->numeric(),
                TextEntry::make('duration')
                    ->label('时长')
                    ->numeric(),
                TextEntry::make('played_at')
                    ->label('游戏时间')
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
