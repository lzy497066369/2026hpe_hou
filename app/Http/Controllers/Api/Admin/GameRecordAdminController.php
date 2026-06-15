<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\GameRecord;
use App\Services\Auth\CurrentUserResolver;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class GameRecordAdminController extends Controller
{
    public function index(Request $request, CurrentUserResolver $resolver): JsonResponse
    {
        $this->requireAdmin($request, $resolver);

        return ApiResponse::success(
            GameRecord::query()->with('user')->orderByDesc('score')->paginate((int) $request->query('pageSize', 20))
        );
    }

    public function update(string $recordId, Request $request, CurrentUserResolver $resolver): JsonResponse
    {
        $this->requireAdmin($request, $resolver);
        $record = GameRecord::query()->findOrFail($recordId);
        $record->fill($request->validate([
            'distance' => ['sometimes', 'integer', 'min:0'],
            'score' => ['sometimes', 'integer', 'min:0'],
            'duration' => ['sometimes', 'integer', 'min:0'],
        ]))->save();

        return ApiResponse::success($record);
    }

    public function destroy(string $recordId, Request $request, CurrentUserResolver $resolver): JsonResponse
    {
        $this->requireAdmin($request, $resolver);
        GameRecord::query()->findOrFail($recordId)->delete();

        return ApiResponse::success(['success' => true]);
    }

    private function requireAdmin(Request $request, CurrentUserResolver $resolver): void
    {
        $user = $resolver->require($request);
        abort_if($user->role !== 'admin', 403, 'Forbidden.');
    }
}
