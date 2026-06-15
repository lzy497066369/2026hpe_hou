<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Services\Admin\AdminStatisticsService;
use App\Services\Auth\CurrentUserResolver;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class StatisticsController extends Controller
{
    public function overview(Request $request, AdminStatisticsService $service, CurrentUserResolver $resolver): JsonResponse
    {
        $user = $resolver->require($request);
        abort_if($user->role !== 'admin', 403, 'Forbidden.');

        return ApiResponse::success($service->overview());
    }
}
