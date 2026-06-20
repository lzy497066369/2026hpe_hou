@php
    $rows = collect($rows ?? []);
@endphp

<div class="award-settlement-list">
    <div class="award-settlement-list__summary">
        <span>名单数据</span>
        <strong>{{ $rows->count() }}</strong>
        <span>条</span>
    </div>

    @if ($rows->isEmpty())
        <div class="award-settlement-empty">
            <strong>暂无数据</strong>
            <span>当前条件下还没有可展示的名单记录。</span>
        </div>
    @else
        <div class="award-settlement-records">
            @foreach ($rows as $row)
                @php
                    $sourceLabel = $row['track'] ?? $row['prize_name'] ?? '-';
                    $metricLabel = '-';

                    if (isset($row['work_title'])) {
                        $metricLabel = $row['work_title'] ?: '-';

                        if (array_key_exists('vote_count', $row)) {
                            $metricLabel .= ' / ' . ($row['vote_count'] ?? 0) . ' 票';
                        }
                    } elseif (isset($row['score'])) {
                        $metricLabel = ($row['score'] ?? 0) . ' 分 / ' . ($row['distance'] ?? 0) . ' 米';
                    } elseif (isset($row['weight'])) {
                        $metricLabel = ($row['weight'] ?? 0) . ' 权重';
                    }

                    $hasEligibility = array_key_exists('eligible', $row);
                    $statusLabel = $hasEligibility
                        ? ($row['eligible'] ? '可抽奖' : ($row['reason'] ?? '不可抽奖'))
                        : ($row['reason'] ?? $row['drawn_at'] ?? '-');
                @endphp

                <article class="award-settlement-record">
                    <div class="award-settlement-record__avatar" aria-hidden="true">
                        {{ mb_substr((string) ($row['name'] ?? $row['nickname'] ?? 'H'), 0, 1) }}
                    </div>

                    <div class="award-settlement-record__main">
                        <div class="award-settlement-record__header">
                            <div>
                                <h3>{{ $row['name'] ?? '未填写姓名' }}</h3>
                                <p>{{ $row['employee_no'] ?? '-' }} · {{ $row['nickname'] ?? '-' }}</p>
                            </div>

                            <span @class([
                                'award-settlement-record__status',
                                'award-settlement-record__status--success' => $hasEligibility && $row['eligible'],
                                'award-settlement-record__status--danger' => $hasEligibility && ! $row['eligible'],
                            ])>
                                {{ $statusLabel }}
                            </span>
                        </div>

                        <dl class="award-settlement-record__grid">
                            <div>
                                <dt>赛道/奖项</dt>
                                <dd>{{ $sourceLabel }}</dd>
                            </div>
                            <div>
                                <dt>作品/分数</dt>
                                <dd>{{ $metricLabel }}</dd>
                            </div>
                            <div>
                                <dt>邮箱</dt>
                                <dd>{{ $row['email'] ?? '-' }}</dd>
                            </div>
                        </dl>
                    </div>
                </article>
            @endforeach
        </div>
    @endif
</div>
