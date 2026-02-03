<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class EmailVerificationController extends Controller
{
    // メール認証誘導画面を表示する
    public function show()
    {
        // パスに合わせて email-verify を指定
        return view('auth.email-verify');
    }

    // 再送処理（見た目確認後、ロジック実装時に使用）
    public function resend(Request $request)
    {
        // ここに再送ロジックを記述
        return back()->with('message', 'verification-link-sent');
    }
}
