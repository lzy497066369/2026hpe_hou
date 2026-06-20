<?php

namespace App\Filament\Exports;

use App\Filament\Exports\Concerns\ExportsCsvOnly;
use App\Models\LotteryRecord;
use App\Support\AdminDisplay;
use Filament\Actions\Exports\ExportColumn;
use Filament\Actions\Exports\Exporter;

class LotteryRecordExporter extends Exporter
{
    use ExportsCsvOnly;

    protected static ?string $model = LotteryRecord::class;

    public static function getColumns(): array
    {
        return [
            ExportColumn::make('user_id')->label('内部用户编号'),
            ExportColumn::make('user.name')->label('Preferred Name')
                ->formatStateUsing(fn (LotteryRecord $record): string => AdminDisplay::preferredName($record->user)),
            ExportColumn::make('user.employee_no')->label('员工号'),
            ExportColumn::make('user.email')->label('邮箱'),
            ExportColumn::make('user.address')->label('城市'),
            ExportColumn::make('user.work_address_code')->label('城市code'),
            ExportColumn::make('prize.name')->label('奖品'),
            ExportColumn::make('work.title')->label('关联作品'),
            ExportColumn::make('result_status')->label('中奖状态')
                ->formatStateUsing(fn (?string $state): string => AdminDisplay::lotteryStatus($state)),
            ExportColumn::make('prizeClaim.claim_status')->label('领奖状态')
                ->formatStateUsing(fn (?string $state): string => AdminDisplay::claimStatus($state)),
            ExportColumn::make('prizeClaim.claim_type')->label('奖品领取方式')
                ->formatStateUsing(fn (?string $state): string => AdminDisplay::claimType($state)),
            ExportColumn::make('prizeClaim.receiver_name')->label('姓名'),
            ExportColumn::make('prizeClaim.receiver_phone')->label('电话'),
            ExportColumn::make('prizeClaim.receiver_address')->label('地址'),
            ExportColumn::make('drawn_at')->label('开奖时间'),
            ExportColumn::make('created_at')->label('创建时间'),
            ExportColumn::make('updated_at')->label('更新时间'),
        ];
    }
}
