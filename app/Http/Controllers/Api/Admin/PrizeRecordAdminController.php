<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\LotteryRecord;
use App\Services\Admin\FinalAwardService;
use App\Services\Auth\CurrentUserResolver;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PrizeRecordAdminController extends Controller
{
    public function index(Request $request, CurrentUserResolver $resolver): JsonResponse
    {
        $this->requireAdmin($request, $resolver);

        return ApiResponse::success(
            LotteryRecord::query()->with(['prize', 'prizeClaim'])->orderByDesc('created_at')->paginate((int) $request->query('pageSize', 20))
        );
    }

    public function update(string $recordId, Request $request, CurrentUserResolver $resolver): JsonResponse
    {
        $this->requireAdmin($request, $resolver);
        $record = LotteryRecord::query()->findOrFail($recordId);
        $record->fill($request->validate([
            'result_status' => ['sometimes', 'in:pending,won,lost'],
            'prize_id' => ['sometimes', 'nullable', 'integer', 'exists:prizes,id'],
        ]))->save();

        return ApiResponse::success($record->fresh('prize'));
    }

    public function calculateFinalAwards(Request $request, CurrentUserResolver $resolver, FinalAwardService $service): JsonResponse
    {
        $this->requireAdmin($request, $resolver);

        return ApiResponse::success($service->calculate());
    }

    private function requireAdmin(Request $request, CurrentUserResolver $resolver): void
    {
        $user = $resolver->require($request);
        abort_if($user->role !== 'admin', 403, 'Forbidden.');
    }
}
