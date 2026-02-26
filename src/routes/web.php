<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Auth;
use App\Http\Controllers\User;
use App\Http\Controllers\Admin;


Route::get('/register', [Auth\RegisterController::class, 'show'])->name('register');
Route::post('/register', [Auth\RegisterController::class, 'store']);
Route::get('/login', [Auth\LoginController::class, 'show'])->name('login');
Route::post('/login', [Auth\LoginController::class, 'store']);
Route::post('/logout', [Auth\LogoutController::class, 'logout'])->name('logout');
Route::get('/verify-email', [Auth\EmailVerificationController::class, 'show'])->name('verification.notice');
Route::get('/email/verify/{id}/{hash}', [Auth\EmailVerificationController::class, 'verify'])
    ->middleware(['auth', 'signed'])
    ->name('verification.verify');
Route::post('/email/verification-notification', [Auth\EmailVerificationController::class, 'resend'])
    ->name('verification.resend');


Route::get('/admin/login', [Auth\AdminLoginController::class, 'show'])->name('admin.login.view');
Route::post('/admin/login', [Auth\AdminLoginController::class, 'store'])->name('admin.login');


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


Route::middleware(['auth:web', 'verified'])->group(function () {
    Route::get('/attendance', [User\StampController::class, 'index'])->name('attendance.index');
    Route::post('/attendance', [User\StampController::class, 'store'])->name('attendance.store');
    Route::get('/attendance/list', [User\MonthlyController::class, 'index'])->name('attendance.list');
    Route::get('/attendance/detail/{id}', [User\WorkDetailController::class, 'show'])->name('attendance.detail');
    Route::post('/attendance/detail/{id}', [User\WorkDetailController::class, 'update'])->name('attendance.update');
});


Route::get('/stamp_correction_request/list', function (\Illuminate\Http\Request $request) {
    if (\Illuminate\Support\Facades\Auth::guard('admin')->check()) {
        return app(\App\Http\Controllers\Admin\CorrectionRequestController::class)->index($request);
    }
    if (\Illuminate\Support\Facades\Auth::guard('web')->check()) {
        return app(\App\Http\Controllers\User\MyRequestController::class)->index($request);
    }
    return redirect()->route('login');
})->name('attendance.request.list');
