<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\LoginRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException; // これが必要

class LoginController extends Controller
{
    public function show()
    {
        return view('auth.login');
    }

    public function store(LoginRequest $request)
    {
        // 'web' ガードを明示的に指定
        if (!Auth::guard('web')->check()) {
            if (!Auth::guard('web')->attempt($request->only('email', 'password'))) {
                throw ValidationException::withMessages([
                    'login_error' => 'ログイン情報が登録されていません',
                ]);
            }
        }
        $request->session()->regenerate();

       // ログインしたユーザー情報を取得
        /** @var \App\Models\User $user */
        $user = Auth::guard('web')->user(); // ここも guard を指定

        // 【重要】ここで手動チェックを入れることで、ミドルウェアとの衝突を防ぐ
        if ($user->hasVerifiedEmail()) {
            // 認証済みなら打刻画面へ
            return redirect()->route('attendance.index');
        }

        // 未認証ならメール確認誘導画面へ
        return redirect()->route('verification.notice');
    }
}
