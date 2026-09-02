<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\ReportController;
use App\Http\Controllers\Api\AspirationController;
use App\Http\Controllers\Api\FacilityReportController;
use App\Http\Controllers\Api\BullyingReportController;
use App\Http\Controllers\Api\ReportCommentController;
use App\Http\Controllers\Api\ReportAttachmentController;
use App\Http\Controllers\Api\NotificationController;
use App\Http\Controllers\Api\Admin\UserManagementController;
use App\Http\Controllers\Api\Admin\DashboardController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| PUBLIC ROUTES (tanpa auth)
|--------------------------------------------------------------------------
*/

Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);

/*
|--------------------------------------------------------------------------
| AUTHENTICATED ROUTES (semua role, wajib login)
|--------------------------------------------------------------------------
*/
Route::middleware('auth:sanctum')->group(function () {

    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/me', [AuthController::class, 'me']);

    // ================= REPORTS (umum, dicek Policy per-request) =================
    Route::get('/reports', [ReportController::class, 'index']);          // list, filtered by role di controller
    Route::get('/reports/{report}', [ReportController::class, 'show']);  // detail, authorize('view') di dalamnya
    Route::patch('/reports/{report}', [ReportController::class, 'update']); // edit sebelum diproses (pemilik)
    Route::patch('/reports/{report}/status', [ReportController::class, 'updateStatus']); // staff/BK/admin
    Route::patch('/reports/{report}/assign', [ReportController::class, 'assign']);

    // ================= ASPIRASI =================
    Route::get('/aspirations', [AspirationController::class, 'index']); // feed publik
    Route::post('/aspirations', [AspirationController::class, 'store']);
    Route::post('/aspirations/{report}/upvote', [AspirationController::class, 'upvote']);

    // ================= PENGADUAN FASILITAS =================
    Route::post('/facility-reports', [FacilityReportController::class, 'store']);
    Route::get('/facility-reports', [FacilityReportController::class, 'index'])
        ->middleware('permission:facility.view_all'); // hanya staff/admin

    // ================= PENGADUAN BULLYING =================
    Route::post('/bullying-reports', [BullyingReportController::class, 'store']);
    Route::get('/bullying-reports', [BullyingReportController::class, 'index'])
        ->middleware('permission:bullying.handle'); // HANYA counselor, bukan admin
    Route::patch('/bullying-reports/{report}/handle', [BullyingReportController::class, 'handle'])
        ->middleware('permission:bullying.handle');

    // ================= KOMENTAR =================
    Route::get('/reports/{report}/comments', [ReportCommentController::class, 'index']);
    Route::post('/reports/{report}/comments', [ReportCommentController::class, 'store']);

    // ================= LAMPIRAN =================
    Route::post('/reports/{report}/attachments', [ReportAttachmentController::class, 'store']);
    Route::delete('/attachments/{attachment}', [ReportAttachmentController::class, 'destroy']);

    // ================= NOTIFIKASI =================
    Route::get('/notifications', [NotificationController::class, 'index']);
    Route::patch('/notifications/{notification}/read', [NotificationController::class, 'markAsRead']);
    Route::patch('/notifications/read-all', [NotificationController::class, 'markAllAsRead']);

    /*
    |--------------------------------------------------------------------------
    | ADMIN ONLY ROUTES
    |--------------------------------------------------------------------------
    */
    Route::prefix('admin')->middleware('role:admin')->group(function () {
        Route::get('/dashboard/analytics', [DashboardController::class, 'analytics']);
        Route::get('/reports/export', [DashboardController::class, 'export']);

        Route::apiResource('users', UserManagementController::class);
        Route::patch('/users/{user}/toggle-active', [UserManagementController::class, 'toggleActive']);
        Route::post('/users/{user}/assign-role', [UserManagementController::class, 'assignRole']);
    });

    /*
    |--------------------------------------------------------------------------
    | STAFF ONLY ROUTES
    |--------------------------------------------------------------------------
    */
    Route::prefix('staff')->middleware('permission:facility.manage')->group(function () {
        Route::get('/facility-queue', [FacilityReportController::class, 'queue']);
    });

    /*
    |--------------------------------------------------------------------------
    | COUNSELOR (BK) ONLY ROUTES
    |--------------------------------------------------------------------------
    */
    Route::prefix('counselor')->middleware('permission:bullying.handle')->group(function () {
        Route::get('/bullying-queue', [BullyingReportController::class, 'queue']);
    });
});
