<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\LoginRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

class LoginController extends Controller
{
    public function show()
    {
        return view('auth.login');
    }

    public function store(LoginRequest $request)
    {
        Auth::guard('web')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        if (!Auth::guard('web')->attempt($request->only('email', 'password'))) {
            throw ValidationException::withMessages([
                'login_error' => 'ログイン情報が登録されていません',
            ]);
        }
        $request->session()->regenerate();
        /** @var \App\Models\User $user */
        $user = Auth::guard('web')->user();
        if ($user->hasVerifiedEmail()) {
            return redirect()->route('attendance.index');
        }
        return redirect()->route('verification.notice');
    }
}
