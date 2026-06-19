<?php

namespace App\Services\Work;

use App\Enums\UploadUsageType;
use App\Enums\WorkAuditStatus;
use App\Enums\WorkPublishStatus;
use App\Models\UploadedFile;
use App\Models\User;
use App\Models\Work;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class WorkService
{
    private const TRADITIONAL_CONTENT_MIME_TYPES = [
        'image/jpeg',
        'image/png',
        'image/webp',
        'audio/mpeg',
        'audio/mp3',
    ];

    private const AI_CONTENT_MIME_TYPES = [
        'image/jpeg',
        'image/png',
        'image/webp',
        'video/mp4',
        'audio/mpeg',
        'audio/mp3',
    ];

    /**
     * @return array<string, mixed>
     */
    public function list(
        int $page = 1,
        int $pageSize = 10,
        ?string $group = null,
        ?string $type = null,
        ?string $keyword = null
    ): array
    {
        $query = Work::query()
            ->with(['user', 'coverFile', 'contentFile'])
            ->where('publish_status', WorkPublishStatus::Published->value);

        if ($group !== null && $group !== '') {
            $query->where('group', $group);
        }

        if ($type !== null && $type !== '') {
            $query->where('type', $type);
        }

        $keyword = trim((string) $keyword);

        if ($keyword !== '') {
            return $this->listByKeywordWithSerial($query, $page, $pageSize, $keyword);
        }

        $this->applyListOrder($query);

        $paginator = $query->paginate($pageSize, ['*'], 'page', $page);

        return [
            'items' => $paginator->getCollection()
                ->values()
                ->map(fn (Work $work): array => $this->formatWork($work))
                ->all(),
            'pagination' => [
                'page' => $paginator->currentPage(),
                'pageSize' => $paginator->perPage(),
                'total' => $paginator->total(),
                'hasMore' => $paginator->hasMorePages(),
            ],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function detail(string $workId, ?User $viewer = null): array
    {
        $query = Work::query()
            ->with(['user', 'coverFile', 'contentFile'])
            ->where('id', $workId);

        if ($viewer === null) {
            $query->where('publish_status', WorkPublishStatus::Published->value);
        } else {
            $query->where(function ($builder) use ($viewer): void {
                $builder
                    ->where('publish_status', WorkPublishStatus::Published->value)
                    ->orWhere('user_id', $viewer->id);
            });
        }

        $work = $query->firstOrFail();

        return $this->formatWork($work);
    }

    /**
     * @param array<string, mixed> $payload
     * @return array<string, mixed>
     */
    public function submit(User $user, array $payload): array
    {
        $work = DB::transaction(function () use ($user, $payload): Work {
            $lockedUser = User::query()
                ->whereKey($user->id)
                ->lockForUpdate()
                ->firstOrFail();

            abort_if($this->usedWorkQuota($lockedUser) >= $this->availableWorkQuota($lockedUser), 422, '当前可上传作品名额已用完，请申请更多名额');

            $content = UploadedFile::query()
                ->where('id', $payload['contentFileId'])
                ->where('user_id', $lockedUser->id)
                ->where('usage_type', UploadUsageType::WorkContent->value)
                ->first();

            abort_if($content === null, 422, '作品内容文件不存在或用途不正确');

            $this->ensureContentMimeTypeIsAllowed($payload['type'], (string) $content->mime_type);

            $coverFileId = $payload['coverFileId'] ?? null;
            if ($coverFileId !== null) {
                $cover = UploadedFile::query()
                    ->where('id', $coverFileId)
                    ->where('user_id', $lockedUser->id)
                    ->where('usage_type', UploadUsageType::WorkCover->value)
                    ->first();
                abort_if($cover === null, 422, '作品封面文件不存在或用途不正确');
            }

            $work = Work::query()->create([
                'user_id' => $lockedUser->id,
                'type' => $payload['type'],
                'group' => $payload['group'],
                'title' => $payload['title'],
                'description' => $payload['description'] ?? null,
                'cover_file_id' => $coverFileId,
                'content_file_id' => $payload['contentFileId'],
                'tool_name' => $payload['toolName'] ?? null,
                'prompt_text' => $payload['promptText'] ?? null,
                'audit_status' => WorkAuditStatus::Submitted->value,
                'publish_status' => WorkPublishStatus::Hidden->value,
                'submitted_at' => now(),
            ]);

            UploadedFile::query()
                ->whereIn('id', array_filter([$coverFileId, $payload['contentFileId']]))
                ->where('user_id', $lockedUser->id)
                ->update(['is_committed' => true]);

            return $work;
        });

        return $this->formatWork($work->load(['user', 'coverFile', 'contentFile']));
    }

    /**
     * @param array<string, mixed> $payload
     * @return array<string, mixed>
     */
    public function update(User $user, string $workId, array $payload): array
    {
        $work = Work::query()
            ->with(['user', 'coverFile', 'contentFile'])
            ->where('id', $workId)
            ->where('user_id', $user->id)
            ->firstOrFail();

        $work->fill([
            'title' => $payload['title'] ?? $work->title,
            'description' => array_key_exists('description', $payload) ? $payload['description'] : $work->description,
            'tool_name' => array_key_exists('toolName', $payload) ? $payload['toolName'] : $work->tool_name,
            'prompt_text' => array_key_exists('promptText', $payload) ? $payload['promptText'] : $work->prompt_text,
        ])->save();

        return $this->formatWork($work->fresh(['user', 'coverFile', 'contentFile']));
    }

    /**
     * @return array<string, mixed>
     */
    public function mine(User $user, int $page = 1, int $pageSize = 10): array
    {
        $paginator = Work::query()
            ->with(['user', 'coverFile', 'contentFile'])
            ->where('user_id', $user->id)
            ->orderByDesc('created_at')
            ->paginate($pageSize, ['*'], 'page', $page);

        return [
            'items' => $paginator->getCollection()
                ->map(fn (Work $work): array => $this->formatWork($work))
                ->all(),
            'pagination' => [
                'page' => $paginator->currentPage(),
                'pageSize' => $paginator->perPage(),
                'total' => $paginator->total(),
                'hasMore' => $paginator->hasMorePages(),
            ],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function formatWork(Work $work): array
    {
        return [
            'id' => (string) $work->id,
            'serial' => (int) $work->id,
            'type' => $work->type,
            'group' => $work->group,
            'title' => $work->title,
            'description' => $work->description ?? '',
            'employeeNo' => $work->user?->employee_no,
            'authorName' => $work->user?->nickname ?: ($work->user?->name ?: $work->user?->employee_no),
            'coverUrl' => $this->fullUrl($work->coverFile?->url),
            'coverMimeType' => $work->coverFile?->mime_type,
            'contentUrl' => $this->fullUrl($work->contentFile?->url),
            'contentMimeType' => $work->contentFile?->mime_type,
            'contentFileId' => $work->content_file_id === null ? null : (string) $work->content_file_id,
            'toolName' => $work->tool_name,
            'promptText' => $work->prompt_text,
            'auditStatus' => $work->audit_status,
            'publishStatus' => $work->publish_status,
            'voteCount' => $work->vote_count,
        ];
    }

    private function fullUrl(?string $url): ?string
    {
        if ($url === null || $url === '') {
            return null;
        }

        $path = parse_url($url, PHP_URL_PATH);
        if (is_string($path) && str_starts_with($path, '/storage/')) {
            return rtrim((string) config('app.url'), '/').$path;
        }

        if (preg_match('/^https?:\/\//i', $url) === 1) {
            return $url;
        }

        return rtrim((string) config('app.url'), '/').'/'.ltrim($url, '/');
    }

    /**
     * @param Builder<Work> $query
     * @return array<string, mixed>
     */
    private function listByKeywordWithSerial(Builder $query, int $page, int $pageSize, string $keyword): array
    {
        $works = $this->applyListOrder($query)
            ->get()
            ->values();

        $matchedWorks = $works
            ->map(fn (Work $work, int $index): array => [
                'work' => $work,
                'serial' => (int) $work->id,
            ])
            ->filter(fn (array $item): bool => $this->matchesKeywordWithSerial($item['serial'], $keyword))
            ->values();

        return $this->formatPaginatedCollection($matchedWorks, $page, $pageSize);
    }

    /**
     * @param Builder<Work> $query
     * @return Builder<Work>
     */
    private function applyListOrder(Builder $query): Builder
    {
        return $query
            ->orderByDesc('vote_count')
            ->orderByDesc('created_at')
            ->orderByDesc('id');
    }

    private function matchesKeywordWithSerial(int $serial, string $keyword): bool
    {
        $normalizedKeyword = $this->normalizeSearchText($keyword);
        $serialLabel = str_pad((string) $serial, 3, '0', STR_PAD_LEFT);
        $searchableText = $this->normalizeSearchText(implode(' ', [
            (string) $serial,
            $serialLabel,
            '#'.$serial,
            '#'.$serialLabel,
        ]));

        return str_contains($searchableText, $normalizedKeyword);
    }

    private function normalizeSearchText(string $text): string
    {
        return mb_strtolower(trim($text));
    }

    /**
     * @param Collection<int, array{work: Work, serial: int}> $works
     * @return array<string, mixed>
     */
    private function formatPaginatedCollection(Collection $works, int $page, int $pageSize): array
    {
        $safePage = max(1, $page);
        $safePageSize = max(1, $pageSize);
        $total = $works->count();
        $offset = ($safePage - 1) * $safePageSize;

        return [
            'items' => $works
                ->slice($offset, $safePageSize)
                ->values()
                ->map(fn (array $item): array => $this->formatWork($item['work']))
                ->all(),
            'pagination' => [
                'page' => $safePage,
                'pageSize' => $safePageSize,
                'total' => $total,
                'hasMore' => $offset + $safePageSize < $total,
            ],
        ];
    }

    private function availableWorkQuota(User $user): int
    {
        return $user->availableWorkQuota();
    }

    private function usedWorkQuota(User $user): int
    {
        return $user->usedWorkQuota();
    }

    private function ensureContentMimeTypeIsAllowed(string $workType, string $mimeType): void
    {
        $allowedMimeTypes = $workType === 'ai'
            ? self::AI_CONTENT_MIME_TYPES
            : self::TRADITIONAL_CONTENT_MIME_TYPES;

        $message = $workType === 'ai'
            ? 'AI 创作仅支持图片、视频或音频文件'
            : '传统创作仅支持图片或音频文件';

        abort_if(! in_array($mimeType, $allowedMimeTypes, true), 422, $message);
    }
}
