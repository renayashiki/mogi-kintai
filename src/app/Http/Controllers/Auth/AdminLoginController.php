<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\LoginRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

class AdminLoginController extends Controller
{
    /**
     * 管理者ログイン画面の表示
     */
    public function show()
    {
        return view('auth.admin-login');
    }

    /**
     * 管理者ログイン処理
     */
    public function store(LoginRequest $request)
    {
        // 管理者用ガード 'admin' を使用して認証を試行
        if (!Auth::guard('admin')->attempt($request->only('email', 'password'))) {
            throw ValidationException::withMessages([
                'login_error' => 'ログイン情報が登録されていません',
            ]);
        }

        $request->session()->regenerate();

        // ログイン成功後は管理者用の勤怠一覧画面へ
        return redirect()->route('admin.attendance.list');
    }
}
