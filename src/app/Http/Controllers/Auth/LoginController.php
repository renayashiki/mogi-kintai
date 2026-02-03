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
        // 前回のロジックをそのまま適用
        if (!Auth::check()) {
            if (!Auth::attempt($request->only('email', 'password'))) {
                throw ValidationException::withMessages([
                    'login_error' => 'ログイン情報が登録されていません',
                ]);
            }
        }

        $request->session()->regenerate();

        return redirect()->intended(route('attendance.index'));
    }
}
