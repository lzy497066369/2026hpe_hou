<?php

namespace App\Filament\Resources\LotteryRecords\Schemas;

use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class LotteryRecordForm
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
                Select::make('prize_id')
                    ->label('奖品')
                    ->relationship('prize', 'name')
                    ->searchable(),
                Select::make('work_id')
                    ->label('关联作品')
                    ->relationship('work', 'title')
                    ->searchable(),
                Select::make('result_status')
                    ->label('中奖状态')
                    ->options([
                        'pending' => '待开奖',
                        'won' => '已中奖',
                        'missed' => '未中奖',
                    ])
                    ->required()
                    ->default('pending'),
                DateTimePicker::make('drawn_at')
                    ->label('开奖时间'),
            ]);
    }
}
