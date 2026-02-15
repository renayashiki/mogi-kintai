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
        // --- 追加: 念のため現在のログイン状態を解除してクリーンにする ---
        Auth::guard('web')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        // 1. 入力された情報でログインを試みる
        if (!Auth::guard('web')->attempt($request->only('email', 'password'))) {
            throw ValidationException::withMessages([
                'login_error' => 'ログイン情報が登録されていません',
            ]);
        }

        // 2. ログイン成功後のセッション再生成（セッション固定攻撃対策）
        $request->session()->regenerate();

        /** @var \App\Models\User $user */
        $user = Auth::guard('web')->user();

        // 3. メール認証状況を確認
        if ($user->hasVerifiedEmail()) {
            // 認証済みなら打刻画面へ
            return redirect()->route('attendance.index');
        }

        // 未認証ならメール確認誘導画面へ
        return redirect()->route('verification.notice');
    }
}
