<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Auth;
use App\Http\Controllers\User;
use App\Http\Controllers\Admin;

// --- 一般ユーザー（認証用ルートはFortifyが生成しますが、念のため明記） ---
Route::get('/register', [Auth\RegisterController::class, 'show'])->name('register');
Route::post('/register', [Auth\RegisterController::class, 'store']);
Route::get('/login', [Auth\LoginController::class, 'show'])->name('login');
Route::post('/login', [Auth\LoginController::class, 'store']);
Route::post('/logout', [Auth\LogoutController::class, 'logout'])->name('logout');
Route::get('/verify-email', [Auth\EmailVerificationController::class, 'show'])->name('verification.notice');
// 2. ★追加：メール内のリンクをクリックした時の処理（これがないと認証が完了しません）
Route::get('/email/verify/{id}/{hash}', [Auth\EmailVerificationController::class, 'verify'])
    ->middleware(['auth', 'signed'])
    ->name('verification.verify');

// 3. 再送処理（現状のまま）
Route::post('/email/verification-notification', [Auth\EmailVerificationController::class, 'resend'])
    ->name('verification.resend');


// --- 管理者ログイン ---
Route::get('/admin/login', [Auth\AdminLoginController::class, 'show'])->name('admin.login.view');
Route::post('/admin/login', [Auth\AdminLoginController::class, 'store'])->name('admin.login');

// --- 管理者ユーザー ---
Route::middleware(['auth:admin'])->group(function () {
    Route::post('/logout', [Auth\LogoutController::class, 'logout'])->name('logout');
    Route::get('/admin/attendance/list', [Admin\DailyController::class, 'index'])->name('admin.attendance.list');
    Route::get('/admin/attendance/{id}', [Admin\EditController::class, 'show'])->name('admin.attendance.detail');
    Route::post('/admin/attendance/update/{id}', [Admin\EditController::class, 'update'])->name('admin.attendance.update');
    Route::get('/admin/staff/list', [Admin\StaffController::class, 'index'])->name('staff.list');
    Route::get('/admin/attendance/staff/{id}', [Admin\StaffLogController::class, 'index'])->name('staff.log');
    Route::get('/admin/attendance/staff/{id}/csv', [Admin\StaffLogController::class, 'exportCsv'])->name('staff.log.csv');
    Route::get('/stamp_correction_request/approve/{attendance_correct_request_id}', [Admin\ApprovalController::class, 'show'])->name('admin.request.approve');
    Route::post('/stamp_correction_request/approve/{attendance_correct_request_id}', [Admin\ApprovalController::class, 'approve'])->name('admin.attendance.approve');
});

// --- 一般ユーザー（勤怠関連） ---
Route::middleware(['auth:web', 'verified'])->group(function () {
    Route::get('/attendance', [User\StampController::class, 'index'])->name('attendance.index');
    Route::post('/attendance', [User\StampController::class, 'store'])->name('attendance.store');
    Route::get('/attendance/list', [User\MonthlyController::class, 'index'])->name('attendance.list');
    Route::get('/attendance/detail/{id}', [User\WorkDetailController::class, 'show'])->name('attendance.detail');
    Route::post('/attendance/detail/{id}', [User\WorkDetailController::class, 'update'])->name('attendance.update');
    // Route::get('/stamp_correction_request/list', [User\MyRequestController::class, 'index'])->name('attendance.request.list');
});

// 申請一覧：共通パス（名前を1つに統一）
// 申請一覧：共通パス
Route::get('/stamp_correction_request/list', function (\Illuminate\Http\Request $request) {
    // フルパス (\Illuminate\Support\Facades\Auth) で書くことで、
    // 上の use App\Http\Controllers\Auth との衝突を避けます
    if (\Illuminate\Support\Facades\Auth::guard('admin')->check()) {
        return app(\App\Http\Controllers\Admin\CorrectionRequestController::class)->index($request);
    }
    if (\Illuminate\Support\Facades\Auth::guard('web')->check()) {
        return app(\App\Http\Controllers\User\MyRequestController::class)->index($request);
    }
    return redirect()->route('login');
})->name('attendance.request.list');
