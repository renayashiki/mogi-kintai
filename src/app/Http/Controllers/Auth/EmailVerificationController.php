<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Foundation\Auth\EmailVerificationRequest;
use Illuminate\Http\Request;

class EmailVerificationController extends Controller
{
    // メール認証誘導画面を表示
    public function show()
    {
        return view('auth.email-verify');
    }

    // メールリンククリック時の認証処理
    public function verify(EmailVerificationRequest $request)
    {
        $request->fulfill(); // 認証完了（DB更新）

        return redirect()->route('attendance.index');
    }

    // 認証メールの再送
    public function resend(Request $request)
    {
        $request->user()->sendEmailVerificationNotification();

        return back()->with('message', '確認メールを再送しました。');
    }
}
