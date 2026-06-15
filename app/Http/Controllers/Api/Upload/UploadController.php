<?php

namespace App\Http\Controllers\Api\Upload;

use App\Http\Controllers\Controller;
use App\Http\Requests\Upload\CompleteUploadRequest;
use App\Http\Requests\Upload\CreateUploadPolicyRequest;
use App\Http\Requests\Upload\LocalUploadRequest;
use App\Http\Resources\Upload\UploadedFileResource;
use App\Services\Auth\CurrentUserResolver;
use App\Services\Upload\UploadService;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;

class UploadController extends Controller
{
    public function local(LocalUploadRequest $request, UploadService $service, CurrentUserResolver $resolver): JsonResponse
    {
        return ApiResponse::success(
            (new UploadedFileResource($service->storeLocal(
                $resolver->require($request),
                $request->validated('usageType'),
                $request->file('file')
            )))->resolve()
        );
    }

    public function policy(CreateUploadPolicyRequest $request, UploadService $service, CurrentUserResolver $resolver): JsonResponse
    {
        return ApiResponse::success(
            $service->createPolicy($resolver->require($request), $request->validated('usageType'))
        );
    }

    public function complete(CompleteUploadRequest $request, UploadService $service, CurrentUserResolver $resolver): JsonResponse
    {
        return ApiResponse::success(
            (new UploadedFileResource($service->complete($resolver->require($request), $request->validated('fileId'))))->resolve()
        );
    }
}
