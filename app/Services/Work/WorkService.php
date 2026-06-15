<?php

namespace App\Services\Work;

use App\Enums\RegistrationAuditStatus;
use App\Enums\UploadUsageType;
use App\Enums\WorkAuditStatus;
use App\Enums\WorkPublishStatus;
use App\Models\UploadedFile;
use App\Models\User;
use App\Models\Work;

class WorkService
{
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
            ->where('publish_status', WorkPublishStatus::Published->value)
            ->orderByDesc('vote_count');

        if ($group !== null && $group !== '') {
            $query->where('group', $group);
        }

        if ($type !== null && $type !== '') {
            $query->where('type', $type);
        }

        if ($keyword !== null && $keyword !== '') {
            $query->where(function ($builder) use ($keyword): void {
                $builder
                    ->where('title', 'like', '%'.$keyword.'%')
                    ->orWhere('description', 'like', '%'.$keyword.'%')
                    ->orWhereHas('user', function ($userQuery) use ($keyword): void {
                        $userQuery
                            ->where('employee_no', 'like', '%'.$keyword.'%')
                            ->orWhere('nickname', 'like', '%'.$keyword.'%')
                            ->orWhere('name', 'like', '%'.$keyword.'%');
                    });
            });
        }

        $paginator = $query->paginate($pageSize, ['*'], 'page', $page);

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
    public function detail(string $workId): array
    {
        $work = Work::query()
            ->with(['user', 'coverFile', 'contentFile'])
            ->findOrFail($workId);

        return $this->formatWork($work);
    }

    /**
     * @param array<string, mixed> $payload
     * @return array<string, mixed>
     */
    public function submit(User $user, array $payload): array
    {
        $registration = $user->registrationProfile;
        $canSubmitMultiple = $registration?->audit_status === RegistrationAuditStatus::Approved->value;

        abort_if(!$canSubmitMultiple && $user->works()->exists(), 422, '默认每个账号只能上传一幅作品');

        $content = UploadedFile::query()
            ->where('id', $payload['contentFileId'])
            ->where('user_id', $user->id)
            ->where('usage_type', UploadUsageType::WorkContent->value)
            ->first();

        abort_if($content === null, 422, '作品内容文件不存在或用途不正确');

        $coverFileId = $payload['coverFileId'] ?? null;
        if ($coverFileId !== null) {
            $cover = UploadedFile::query()
                ->where('id', $coverFileId)
                ->where('user_id', $user->id)
                ->where('usage_type', UploadUsageType::WorkCover->value)
                ->first();
            abort_if($cover === null, 422, '作品封面文件不存在或用途不正确');
        }

        $work = Work::query()->create([
            'user_id' => $user->id,
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
            ->where('user_id', $user->id)
            ->update(['is_committed' => true]);

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
            'type' => $work->type,
            'group' => $work->group,
            'title' => $work->title,
            'description' => $work->description ?? '',
            'employeeNo' => $work->user?->employee_no,
            'coverUrl' => $work->coverFile?->url,
            'contentUrl' => $work->contentFile?->url,
            'contentFileId' => $work->content_file_id === null ? null : (string) $work->content_file_id,
            'toolName' => $work->tool_name,
            'promptText' => $work->prompt_text,
            'auditStatus' => $work->audit_status,
            'publishStatus' => $work->publish_status,
            'voteCount' => $work->vote_count,
        ];
    }
}
