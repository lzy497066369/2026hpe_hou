<?php

namespace App\Http\Controllers\Api\Admin;

use App\Enums\WorkAuditStatus;
use App\Enums\WorkPublishStatus;
use App\Http\Controllers\Controller;
use App\Models\Work;
use App\Services\Auth\CurrentUserResolver;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class WorkAdminController extends Controller
{
    public function index(Request $request, CurrentUserResolver $resolver): JsonResponse
    {
        $this->requireAdmin($request, $resolver);

        return ApiResponse::success(
            Work::query()->with('user')->orderByDesc('created_at')->paginate((int) $request->query('pageSize', 20))
        );
    }

    public function update(string $workId, Request $request, CurrentUserResolver $resolver): JsonResponse
    {
        $this->requireAdmin($request, $resolver);
        $work = Work::query()->findOrFail($workId);
        $work->fill($request->only(['title', 'description', 'vote_count']))->save();

        return ApiResponse::success($work->fresh());
    }

    public function approve(string $workId, Request $request, CurrentUserResolver $resolver): JsonResponse
    {
        $this->requireAdmin($request, $resolver);
        $work = Work::query()->findOrFail($workId);
        $work->forceFill([
            'audit_status' => WorkAuditStatus::Published->value,
            'publish_status' => WorkPublishStatus::Published->value,
            'reviewed_at' => now(),
        ])->save();

        return ApiResponse::success($work);
    }

    public function reject(string $workId, Request $request, CurrentUserResolver $resolver): JsonResponse
    {
        $this->requireAdmin($request, $resolver);
        $work = Work::query()->findOrFail($workId);
        $work->forceFill([
            'audit_status' => WorkAuditStatus::Rejected->value,
            'publish_status' => WorkPublishStatus::Hidden->value,
            'reviewed_at' => now(),
        ])->save();

        return ApiResponse::success($work);
    }

    public function adjustVotes(string $workId, Request $request, CurrentUserResolver $resolver): JsonResponse
    {
        $this->requireAdmin($request, $resolver);
        $data = $request->validate(['delta' => ['required', 'integer']]);
        $work = Work::query()->findOrFail($workId);
        $work->forceFill(['vote_count' => max(0, $work->vote_count + $data['delta'])])->save();

        return ApiResponse::success($work);
    }

    private function requireAdmin(Request $request, CurrentUserResolver $resolver): void
    {
        $user = $resolver->require($request);
        abort_if($user->role !== 'admin', 403, 'Forbidden.');
    }
}
