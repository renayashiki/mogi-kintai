<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class LogoutController extends Controller
{
    /**
     * ログアウト処理
     */
    public function logout(Request $request)
    {
        // 1. まず「今、管理者のガードでログインしているか」をチェック
        if (Auth::guard('admin')->check()) {
            // 管理者としてログアウト
            Auth::guard('admin')->logout();
            // 管理者ログイン画面（admin.login）へリダイレクト
            return $this->processSessionAndRedirect($request, 'admin.login');
        }

        // 2. そうでなければ一般ユーザーとしてログアウト
        Auth::logout();
        // 一般ユーザーログイン画面（login）へリダイレクト
        return $this->processSessionAndRedirect($request, 'login');
    }

    /**
     * セッションの無効化と再生成を行い、指定されたルート名へ移動
     */
    private function processSessionAndRedirect(Request $request, string $routeName)
    {
        // セッションを無効化（セキュリティ規約遵守）
        $request->session()->invalidate();
        // CSRFトークンを再生成（セキュリティ規約遵守）
        $request->session()->regenerateToken();

        // 指定されたルート名（'admin.login' または 'login'）に飛ばす
        return redirect()->route($routeName);
    }
}
