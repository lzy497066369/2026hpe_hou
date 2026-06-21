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
        return [];
    }

    protected function awardTalentAction(): Action
    {
        return $this->awardAction(
            'awardTalent',
            '才艺大赛奖',
            fn (AwardSettlementService $service): array => $service->previewTalentAwards(),
            fn (AwardSettlementService $service): array => $service->awardTalentAwards(),
            '确认后将按四个赛道各前 15 名发放，并包含第 15 名同票并列用户。'
        );
    }

    protected function awardGameAction(): Action
    {
        return $this->awardAction(
            'awardGame',
            '线上小游戏奖',
            fn (AwardSettlementService $service): array => $service->previewGameAwards(),
            fn (AwardSettlementService $service): array => $service->awardGameAwards(),
            '确认后将按最高分前 10 名发放，并包含第 10 名同分并列用户。'
        );
    }

    protected function awardParticipationAction(): Action
    {
        return $this->awardAction(
            'awardParticipation',
            '阳光普照奖',
            fn (AwardSettlementService $service): array => $service->previewParticipationAwards(),
            fn (AwardSettlementService $service): array => $service->awardParticipationAwards(),
            '确认后将按作品发放给已发布作品且玩过游戏，并且未获得才艺大赛奖/线上小游戏奖的用户。'
        );
    }

    protected function fragranceWinnersAction(): Action
    {
        return $this->listAction(
            'fragranceWinners',
            '手有余香获奖名单',
            fn (AwardSettlementService $service): array => $service->fragranceWinners(),
        );
    }

    protected function fragranceCandidatesAction(): Action
    {
        return $this->listAction(
            'fragranceCandidates',
            '手有余香权重列表',
            fn (AwardSettlementService $service): array => $service->fragranceCandidates(),
        );
    }

    protected function publishFragranceQualificationsAction(): Action
    {
        return $this->publishQualificationAction(
            'publishFragranceQualifications',
            '发布手有余香抽奖资格',
            fn (AwardSettlementService $service): array => $service->previewFragranceQualifications(),
            fn (AwardSettlementService $service): array => $service->publishFragranceQualifications(),
            '确认后将把符合条件的用户写入手有余香奖抽奖资格，未发布前前台不可抽该奖项。'
        );
    }

    protected function supplementFragranceAwardsAction(): Action
    {
        return Action::make('supplementFragranceAwards')
            ->label('补抽手有余香剩余奖品')
            ->color('danger')
            ->modalHeading('补抽手有余香剩余奖品预览')
            ->modalDescription('确认后将从未抽过且仍有资格的用户中，按给他人投票权重补抽剩余库存。只生成中奖记录，不会给未补中的用户写入未中奖记录。')
            ->modalWidth(Width::SevenExtraLarge)
            ->modalSubmitActionLabel('确认补抽')
            ->modalContent(fn () => view('filament.pages.award-settlement-list', [
                'rows' => app(AwardSettlementService::class)->previewSupplementFragranceAwards(),
            ]))
            ->action(function (): void {
                $result = app(AwardSettlementService::class)->supplementFragranceAwards();
                app(OperationLogger::class)->log('award_settlement', 'supplementFragranceAwards', null, $result);

                Notification::make()
                    ->title('手有余香剩余奖品已补抽')
                    ->body('本次补抽中奖人数：'.$result['count'])
                    ->success()
                    ->send();
            });
    }

    protected function dreamParkWinnersAction(): Action
    {
        return $this->listAction(
            'dreamParkWinners',
            '逐梦乐园获奖名单',
            fn (AwardSettlementService $service): array => $service->dreamParkWinners(),
        );
    }

    protected function dreamParkCandidatesAction(): Action
    {
        return $this->listAction(
            'dreamParkCandidates',
            '逐梦乐园可抽奖名单',
            fn (AwardSettlementService $service): array => $service->dreamParkCandidates(),
        );
    }

    protected function publishDreamParkQualificationsAction(): Action
    {
        return $this->publishQualificationAction(
            'publishDreamParkQualifications',
            '发布逐梦乐园抽奖资格',
            fn (AwardSettlementService $service): array => $service->previewDreamParkQualifications(),
            fn (AwardSettlementService $service): array => $service->publishDreamParkQualifications(),
            '确认后将把符合条件的用户写入逐梦乐园奖抽奖资格，未发布前前台不可抽该奖项。'
        );
    }

    protected function supplementDreamParkAwardsAction(): Action
    {
        return Action::make('supplementDreamParkAwards')
            ->label('补抽逐梦乐园剩余奖品')
            ->color('danger')
            ->modalHeading('补抽逐梦乐园剩余奖品预览')
            ->modalDescription('确认后将从未抽过且仍有资格的用户中，随机补抽剩余库存。只生成中奖记录，不会给未补中的用户写入未中奖记录。')
            ->modalWidth(Width::SevenExtraLarge)
            ->modalSubmitActionLabel('确认补抽')
            ->modalContent(fn () => view('filament.pages.award-settlement-list', [
                'rows' => app(AwardSettlementService::class)->previewSupplementDreamParkAwards(),
            ]))
            ->action(function (): void {
                $result = app(AwardSettlementService::class)->supplementDreamParkAwards();
                app(OperationLogger::class)->log('award_settlement', 'supplementDreamParkAwards', null, $result);

                Notification::make()
                    ->title('逐梦乐园剩余奖品已补抽')
                    ->body('本次补抽中奖人数：'.$result['count'])
                    ->success()
                    ->send();
            });
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

    /**
     * @param callable(AwardSettlementService): array<int, array<string, mixed>> $preview
     * @param callable(AwardSettlementService): array<string, mixed> $publish
     */
    private function publishQualificationAction(string $name, string $label, callable $preview, callable $publish, string $description): Action
    {
        return Action::make($name)
            ->label($label)
            ->color('warning')
            ->modalHeading($label.'预览')
            ->modalDescription($description)
            ->modalWidth(Width::SevenExtraLarge)
            ->modalSubmitActionLabel('确认发布抽奖资格')
            ->modalContent(fn () => view('filament.pages.award-settlement-list', [
                'rows' => $preview(app(AwardSettlementService::class)),
            ]))
            ->action(function () use ($publish, $name, $label): void {
                $result = $publish(app(AwardSettlementService::class));
                app(OperationLogger::class)->log('award_settlement', $name, null, $result);

                Notification::make()
                    ->title($label.'已发布')
                    ->body('本次资格人数：'.$result['count'])
                    ->success()
                    ->send();
            });
    }
}
