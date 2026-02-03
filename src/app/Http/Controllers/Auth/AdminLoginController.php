<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;

class AdminLoginController extends Controller
{
    public function show()
    {
        return view('auth.login');
    } // 管理者も共通ログイン画面ならこちら
}
