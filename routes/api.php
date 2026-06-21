<?php

use App\Http\Controllers\Api\Auth\AuthController;
use App\Http\Controllers\Api\Admin\EmployeeController;
use App\Http\Controllers\Api\Admin\GameRecordAdminController;
use App\Http\Controllers\Api\Admin\PrizeRecordAdminController;
use App\Http\Controllers\Api\Admin\StatisticsController;
use App\Http\Controllers\Api\Admin\WorkAdminController;
use App\Http\Controllers\Api\Game\GameController;
use App\Http\Controllers\Api\Lottery\LotteryController;
use App\Http\Controllers\Api\Profile\ProfileController;
use App\Http\Controllers\Api\Registration\RegistrationController;
use App\Http\Controllers\Api\Upload\UploadController;
use App\Http\Controllers\Api\Voting\VoteController;
use App\Http\Controllers\Api\Work\WorkController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function (): void {
    Route::post('/auth/login', [AuthController::class, 'login'])->middleware('throttle:5,1');
    Route::get('/auth/me', [AuthController::class, 'me']);
    Route::post('/auth/logout', [AuthController::class, 'logout']);

    Route::get('/registration/profile', [RegistrationController::class, 'show']);
    Route::get('/registration/status', [RegistrationController::class, 'status']);
    Route::post('/registration/submit', [RegistrationController::class, 'submit']);

    Route::get('/works', [WorkController::class, 'index']);
    Route::get('/works/mine', [WorkController::class, 'mine']);
    Route::post('/works/submit', [WorkController::class, 'submit']);
    Route::get('/works/{workId}', [WorkController::class, 'show']);
    Route::patch('/works/{workId}', [WorkController::class, 'update']);

    Route::post('/votes', [VoteController::class, 'store']);

    Route::get('/lottery/qualification', [LotteryController::class, 'qualification']);
    Route::post('/lottery/draw', [LotteryController::class, 'draw']);
    Route::get('/lottery/announcements', [LotteryController::class, 'announcements']);
    Route::get('/lottery/prizes/mine', [LotteryController::class, 'myPrizes']);
    Route::post('/lottery/records/{recordId}/claim', [LotteryController::class, 'claim']);

    Route::get('/profile/summary', [ProfileController::class, 'summary']);
    Route::patch('/profile', [ProfileController::class, 'update']);

    Route::post('/game/records', [GameController::class, 'store']);
    Route::get('/game/rankings', [GameController::class, 'rankings']);

    Route::get('/admin/statistics/overview', [StatisticsController::class, 'overview']);
    Route::get('/admin/employees', [EmployeeController::class, 'index']);
    Route::post('/admin/employees', [EmployeeController::class, 'store']);
    Route::patch('/admin/employees/{employeeId}', [EmployeeController::class, 'update']);
    Route::delete('/admin/employees/{employeeId}', [EmployeeController::class, 'destroy']);
    Route::get('/admin/employees/export', [EmployeeController::class, 'export']);
    Route::get('/admin/works', [WorkAdminController::class, 'index']);
    Route::patch('/admin/works/{workId}', [WorkAdminController::class, 'update']);
    Route::post('/admin/works/{workId}/approve', [WorkAdminController::class, 'approve']);
    Route::post('/admin/works/{workId}/reject', [WorkAdminController::class, 'reject']);
    Route::post('/admin/works/{workId}/adjust-votes', [WorkAdminController::class, 'adjustVotes']);
    Route::get('/admin/game-records', [GameRecordAdminController::class, 'index']);
    Route::patch('/admin/game-records/{recordId}', [GameRecordAdminController::class, 'update']);
    Route::delete('/admin/game-records/{recordId}', [GameRecordAdminController::class, 'destroy']);
    Route::get('/admin/prize-records', [PrizeRecordAdminController::class, 'index']);
    Route::patch('/admin/prize-records/{recordId}', [PrizeRecordAdminController::class, 'update']);
    Route::post('/admin/prize-records/calculate-final-awards', [PrizeRecordAdminController::class, 'calculateFinalAwards']);

    Route::post('/uploads/policy', [UploadController::class, 'policy']);
    Route::post('/uploads/complete', [UploadController::class, 'complete']);
    Route::post('/uploads/local', [UploadController::class, 'local']);
});
