<?php

namespace App\Http\Middleware;

use Illuminate\Auth\Middleware\Authenticate as Middleware;

class Authenticate extends Middleware
{
    /**
     * Get the path the user should be redirected to when they are not authenticated.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return string|null
     */
    protected function redirectTo($request)
    {
        if (! $request->expectsJson()) {
            // URLの先頭が 'admin' で始まっていれば、管理者のログイン画面へ
            if ($request->is('admin') || $request->is('admin/*')) {
                return route('admin.login.view');
            }
            // それ以外は一般ユーザーのログイン画面へ
            return route('login');
        }
    }
}
