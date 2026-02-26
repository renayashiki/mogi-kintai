<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\LoginRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

class AdminLoginController extends Controller
{
    public function show()
    {
        return view('auth.admin-login');
    }

    public function store(LoginRequest $request)
    {
        Auth::guard('admin')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        $credentials = $request->only('email', 'password');
        $credentials['admin_status'] = 1;
        if (!Auth::guard('admin')->attempt($credentials)) {
            throw ValidationException::withMessages([
                'login_error' => 'ログイン情報が登録されていません',
            ]);
        }
        $request->session()->regenerate();
        return redirect()->route('admin.attendance.list');
    }
}
