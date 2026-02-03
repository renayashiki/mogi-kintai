<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class EmailVerificationController extends Controller
{
    // メール認証誘導画面を表示する
    public function show()
    {
        return view('auth.verify-email');
    }
}
