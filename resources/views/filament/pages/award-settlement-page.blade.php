<x-filament-panels::page>
    <div class="award-settlement-workspace">
        <section class="award-settlement-actions" aria-label="奖项操作">
            <article class="award-settlement-action-card award-settlement-action-card--primary">
                <div class="award-settlement-action-card__content">
                    <span class="award-settlement-action-card__badge">固定奖项</span>
                    <h2 class="award-settlement-action-card__title">才艺大赛奖</h2>
                    <p class="award-settlement-action-card__meta">四个赛道各前 15 名，包含第 15 名同票并列用户。</p>
                </div>
                <button type="button" class="award-settlement-action-card__button" wire:click="mountAction('awardTalent')">
                    预览并颁奖
                </button>
            </article>

            <article class="award-settlement-action-card">
                <div class="award-settlement-action-card__content">
                    <span class="award-settlement-action-card__badge">固定奖项</span>
                    <h2 class="award-settlement-action-card__title">线上小游戏奖</h2>
                    <p class="award-settlement-action-card__meta">按最高分前 10 名发放，包含第 10 名同分并列用户。</p>
                </div>
                <button type="button" class="award-settlement-action-card__button" wire:click="mountAction('awardGame')">
                    预览并颁奖
                </button>
            </article>

            <article class="award-settlement-action-card">
                <div class="award-settlement-action-card__content">
                    <span class="award-settlement-action-card__badge">固定奖项</span>
                    <h2 class="award-settlement-action-card__title">阳光普照奖</h2>
                    <p class="award-settlement-action-card__meta">发放给已发布作品且玩过游戏，并未获得前两项奖的用户。</p>
                </div>
                <button type="button" class="award-settlement-action-card__button" wire:click="mountAction('awardParticipation')">
                    预览并颁奖
                </button>
            </article>

            <article class="award-settlement-action-card">
                <div class="award-settlement-action-card__content">
                    <span class="award-settlement-action-card__badge">抽奖奖项</span>
                    <h2 class="award-settlement-action-card__title">手有余香奖</h2>
                    <p class="award-settlement-action-card__meta">查看当前获奖人员；权重列表用于核对抽奖池与未满足条件原因。</p>
                </div>
                <div class="award-settlement-action-card__buttons">
                    <button type="button" class="award-settlement-action-card__button" wire:click="mountAction('fragranceWinners')">
                        查看获奖名单
                    </button>
                    <button type="button" class="award-settlement-utility-button" wire:click="mountAction('fragranceCandidates')">
                        权重列表
                    </button>
                </div>
            </article>

            <article class="award-settlement-action-card">
                <div class="award-settlement-action-card__content">
                    <span class="award-settlement-action-card__badge">抽奖奖项</span>
                    <h2 class="award-settlement-action-card__title">逐梦乐园奖</h2>
                    <p class="award-settlement-action-card__meta">查看当前获奖人员；可抽奖名单用于核对作品、游戏、投票条件。</p>
                </div>
                <div class="award-settlement-action-card__buttons">
                    <button type="button" class="award-settlement-action-card__button" wire:click="mountAction('dreamParkWinners')">
                        查看获奖名单
                    </button>
                    <button type="button" class="award-settlement-utility-button" wire:click="mountAction('dreamParkCandidates')">
                        可抽奖名单
                    </button>
                </div>
            </article>
        </section>

        <div class="award-settlement-overview">
            <section class="award-settlement-card award-settlement-card--primary">
                <div class="award-settlement-card__kicker">固定奖项</div>
                <h2>先预览，后写入</h2>
                <p>
                    才艺大赛奖、线上小游戏奖、阳光普照奖会先生成预览名单。确认后才写入中奖记录，重复点击不会为同一用户、同一奖项重复创建记录。
                </p>
                <div class="award-settlement-card__tags" aria-label="固定奖项范围">
                    <span>才艺大赛奖</span>
                    <span>线上小游戏奖</span>
                    <span>阳光普照奖</span>
                </div>
            </section>

            <section class="award-settlement-card">
                <div class="award-settlement-card__kicker">抽奖奖项</div>
                <h2>手有余香奖</h2>
                <p>
                    可查看当前获奖人员，也可查看所有用户的投票权重列表。未满足抽奖条件的用户会在名单卡片中显示原因。
                </p>
                <div class="award-settlement-card__tags" aria-label="手有余香奖操作">
                    <span>获奖名单</span>
                    <span>权重列表</span>
                    <span>原因标注</span>
                </div>
            </section>

            <section class="award-settlement-card">
                <div class="award-settlement-card__kicker">抽奖奖项</div>
                <h2>逐梦乐园奖</h2>
                <p>
                    可查看当前获奖人员，也可查看同时具备发布作品、游戏记录、投票记录的可抽奖名单。
                </p>
                <div class="award-settlement-card__tags" aria-label="逐梦乐园奖条件">
                    <span>已发布作品</span>
                    <span>有游戏记录</span>
                    <span>有投票记录</span>
                </div>
            </section>
        </div>
    </div>
</x-filament-panels::page>
