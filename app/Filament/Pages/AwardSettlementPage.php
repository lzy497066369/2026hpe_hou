<?php

namespace App\Filament\Pages;

use App\Services\Admin\AwardSettlementService;
use App\Services\Admin\OperationLogger;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Support\Enums\Width;
use Filament\Support\Icons\Heroicon;

class AwardSettlementPage extends Page
{
    protected static ?string $title = '奖项结算管理';

    protected static ?string $navigationLabel = '奖项结算管理';

    protected static ?string $slug = 'award-settlement';

    protected static string|\BackedEnum|null $navigationIcon = Heroicon::OutlinedTrophy;

    protected static string|\UnitEnum|null $navigationGroup = '奖项管理';

    protected static ?int $navigationSort = 30;

    protected string $view = 'filament.pages.award-settlement-page';

    /**
     * @return array<Action>
     */
    protected function getHeaderActions(): array
    {
        return [
            $this->awardAction(
                'awardTalent',
                '才艺大赛奖',
                fn (AwardSettlementService $service): array => $service->previewTalentAwards(),
                fn (AwardSettlementService $service): array => $service->awardTalentAwards(),
                '确认后将按四个赛道各前 15 名发放，并包含第 15 名同票并列用户。'
            ),
            $this->awardAction(
                'awardGame',
                '线上小游戏奖',
                fn (AwardSettlementService $service): array => $service->previewGameAwards(),
                fn (AwardSettlementService $service): array => $service->awardGameAwards(),
                '确认后将按最高分前 10 名发放，并包含第 10 名同分并列用户。'
            ),
            $this->awardAction(
                'awardParticipation',
                '阳光普照奖',
                fn (AwardSettlementService $service): array => $service->previewParticipationAwards(),
                fn (AwardSettlementService $service): array => $service->awardParticipationAwards(),
                '确认后将发放给已发布作品且玩过游戏，并且未获得才艺大赛奖/线上小游戏奖的用户。'
            ),
            $this->listAction(
                'fragranceWinners',
                '手有余香获奖名单',
                fn (AwardSettlementService $service): array => $service->fragranceWinners(),
            ),
            $this->listAction(
                'fragranceCandidates',
                '手有余香权重列表',
                fn (AwardSettlementService $service): array => $service->fragranceCandidates(),
            ),
            $this->listAction(
                'dreamParkWinners',
                '逐梦乐园获奖名单',
                fn (AwardSettlementService $service): array => $service->dreamParkWinners(),
            ),
            $this->listAction(
                'dreamParkCandidates',
                '逐梦乐园可抽奖名单',
                fn (AwardSettlementService $service): array => $service->dreamParkCandidates(),
            ),
        ];
    }

    /**
     * @param callable(AwardSettlementService): array<int, array<string, mixed>> $preview
     * @param callable(AwardSettlementService): array<string, mixed> $award
     */
    private function awardAction(string $name, string $label, callable $preview, callable $award, string $description): Action
    {
        return Action::make($name)
            ->label($label)
            ->modalHeading($label.'预览')
            ->modalDescription($description)
            ->modalWidth(Width::SevenExtraLarge)
            ->modalSubmitActionLabel('确认颁奖')
            ->modalContent(fn () => view('filament.pages.award-settlement-list', [
                'rows' => $preview(app(AwardSettlementService::class)),
            ]))
            ->action(function () use ($award, $name, $label): void {
                $result = $award(app(AwardSettlementService::class));
                app(OperationLogger::class)->log('award_settlement', $name, null, $result);

                Notification::make()
                    ->title($label.'已颁奖')
                    ->body('本次名单人数：'.$result['count'])
                    ->success()
                    ->send();
            });
    }

    /**
     * @param callable(AwardSettlementService): array<int, array<string, mixed>> $rows
     */
    private function listAction(string $name, string $label, callable $rows): Action
    {
        return Action::make($name)
            ->label($label)
            ->color('gray')
            ->modalHeading($label)
            ->modalWidth(Width::SevenExtraLarge)
            ->modalSubmitAction(false)
            ->modalCancelActionLabel('关闭')
            ->modalContent(fn () => view('filament.pages.award-settlement-list', [
                'rows' => $rows(app(AwardSettlementService::class)),
            ]));
    }
}
