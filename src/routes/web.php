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

// --- 一般ユーザー（勤怠関連） ---
// Route::middleware(['auth', 'verified'])->group(function () {
Route::get('/attendance', [User\StampController::class, 'index'])->name('attendance.index');
Route::post('/attendance', [User\StampController::class, 'store'])->name('attendance.store');
Route::get('/attendance/list', [User\MonthlyController::class, 'index'])->name('attendance.list');
Route::get('/attendance/detail/{id}', [User\WorkDetailController::class, 'show'])->name('attendance.detail');
Route::get('/stamp_correction_request/list', [User\MyRequestController::class, 'index'])->name('attendance.request.list');
// });

// --- 管理者ユーザー ---
// Route::middleware(['auth:admin'])->group(function () {
Route::get('/admin/attendance/list', [Admin\DailyController::class, 'index'])->name('admin.attendance.list');
Route::get('/admin/attendance/{id}', [Admin\EditController::class, 'show'])->name('admin.attendance.detail');
Route::get('/admin/staff/list', [Admin\StaffController::class, 'index'])->name('staff.list');
Route::get('/admin/attendance/staff/{id}', [Admin\StaffLogController::class, 'index'])->name('staff.log');

// 見た目作成中のミドルウェアコメントアウト中は一般とパスが被っているため、この一覧はミドルウェアONまではコメントアウト。
// 反対に、管理者画面の見た目作成時は一般をコメントアウトすることを忘れずに
// Route::get('/stamp_correction_request/list', [Admin\ApprovalController::class, 'index'])->name('admin.request.list');

Route::get('/stamp_correction_request/approve/{attendance_correct_request_id}', [Admin\ApprovalController::class, 'show'])->name('admin.request.approve');
// });