@php
    $rows = collect($rows ?? []);
@endphp

<div class="space-y-3">
    <div class="text-sm text-gray-600 dark:text-gray-300">
        共 {{ $rows->count() }} 条数据
    </div>

    @if ($rows->isEmpty())
        <div class="rounded-lg border border-dashed border-gray-300 p-6 text-center text-sm text-gray-500 dark:border-gray-700 dark:text-gray-400">
            暂无数据
        </div>
    @else
        <div class="overflow-x-auto rounded-lg border border-gray-200 dark:border-gray-800">
            <table class="min-w-full divide-y divide-gray-200 text-sm dark:divide-gray-800">
                <thead class="bg-gray-50 dark:bg-gray-900">
                    <tr>
                        <th class="px-3 py-2 text-left font-medium text-gray-700 dark:text-gray-200">姓名</th>
                        <th class="px-3 py-2 text-left font-medium text-gray-700 dark:text-gray-200">员工号</th>
                        <th class="px-3 py-2 text-left font-medium text-gray-700 dark:text-gray-200">昵称</th>
                        <th class="px-3 py-2 text-left font-medium text-gray-700 dark:text-gray-200">赛道/奖项</th>
                        <th class="px-3 py-2 text-left font-medium text-gray-700 dark:text-gray-200">作品/分数</th>
                        <th class="px-3 py-2 text-left font-medium text-gray-700 dark:text-gray-200">权重</th>
                        <th class="px-3 py-2 text-left font-medium text-gray-700 dark:text-gray-200">状态/原因</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 bg-white dark:divide-gray-800 dark:bg-gray-950">
                    @foreach ($rows as $row)
                        <tr>
                            <td class="px-3 py-2 text-gray-950 dark:text-white">{{ $row['name'] ?? '-' }}</td>
                            <td class="px-3 py-2 text-gray-700 dark:text-gray-300">{{ $row['employee_no'] ?? '-' }}</td>
                            <td class="px-3 py-2 text-gray-700 dark:text-gray-300">{{ $row['nickname'] ?? '-' }}</td>
                            <td class="px-3 py-2 text-gray-700 dark:text-gray-300">
                                {{ $row['track'] ?? $row['prize_name'] ?? '-' }}
                            </td>
                            <td class="px-3 py-2 text-gray-700 dark:text-gray-300">
                                @if (isset($row['work_title']))
                                    {{ $row['work_title'] }} / {{ $row['vote_count'] ?? 0 }} 票
                                @elseif (isset($row['score']))
                                    {{ $row['score'] }} 分 / {{ $row['distance'] ?? 0 }} 米
                                @else
                                    -
                                @endif
                            </td>
                            <td class="px-3 py-2 text-gray-700 dark:text-gray-300">{{ $row['weight'] ?? '-' }}</td>
                            <td class="px-3 py-2">
                                @if (array_key_exists('eligible', $row))
                                    <span @class([
                                        'inline-flex rounded-md px-2 py-1 text-xs font-medium',
                                        'bg-green-50 text-green-700 dark:bg-green-500/10 dark:text-green-300' => $row['eligible'],
                                        'bg-red-50 text-red-700 dark:bg-red-500/10 dark:text-red-300' => ! $row['eligible'],
                                    ])>
                                        {{ $row['eligible'] ? '可抽奖' : ($row['reason'] ?? '不可抽奖') }}
                                    </span>
                                @else
                                    <span class="text-gray-500 dark:text-gray-400">{{ $row['reason'] ?? $row['drawn_at'] ?? '-' }}</span>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif
</div>
