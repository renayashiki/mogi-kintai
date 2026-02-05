<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Auth\Events\Registered;
use App\Http\Requests\RegisterRequest;
use Illuminate\Support\Facades\Auth;


class RegisterController extends Controller
{
    // 会員登録画面の表示
    public function show()
    {
        return view('auth.register');
    }

    // 会員登録の実行
    public function store(RegisterRequest $request)
    {
        // 2. ユーザー作成
        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'admin_status' => 0,
            'attendance_status' => 'outside',
        ]);

        // 3. メール認証用のイベント発行（これを書かないとメールが飛びません）
        event(new Registered($user));

        Auth::login($user);

        // 4. 指定されたルート名 'verification.notice' （URL: /verify-email）へリダイレクト
        return redirect()->route('verification.notice');
    }
}
