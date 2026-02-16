<?php

namespace Tests\Feature\Auth;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\User;

class AdminLoginTest extends TestCase
{
    use RefreshDatabase;

    /**
     * ID 3: ログイン認証機能（管理者）
     * メールアドレスが未入力の場合、バリデーションメッセージが表示される
     */
    public function test_admin_email_is_required()
    {
        // 1. ユーザー（管理者）を登録する
        // Factory側で admin_status => 1 が設定されているため、パスワードのみ指定
        User::factory()->create([
            'password' => bcrypt('admin123'),
        ]);

        // 2. メールアドレス以外のユーザー情報を入力する
        // 3. ログインの処理を行う
        $response = $this->post('/admin/login', [
            'email' => '',
            'password' => 'admin123',
        ]);

        // 期待挙動: 「メールアドレスを入力してください」というバリデーションメッセージが表示される
        $response->assertSessionHasErrors([
            'email' => 'メールアドレスを入力してください'
        ]);
    }

    /**
     * パスワードが未入力の場合、バリデーションメッセージが表示される
     */
    public function test_admin_password_is_required()
    {
        // 1. ユーザー（管理者）を登録する
        $user = User::factory()->create();

        // 2. パスワード以外のユーザー情報を入力する
        // 3. ログインの処理を行う
        $response = $this->post('/admin/login', [
            'email' => $user->email,
            'password' => '',
        ]);

        // 期待挙動: 「パスワードを入力してください」というバリデーションメッセージが表示される
        $response->assertSessionHasErrors([
            'password' => 'パスワードを入力してください'
        ]);
    }

    /**
     * 登録内容と一致しない場合、バリデーションメッセージが表示される
     */
    public function test_admin_login_fails_with_invalid_credentials()
    {
        // 1. ユーザー（管理者）を登録する
        $user = User::factory()->create([
            'password' => bcrypt('admin123'),
        ]);

        // 2. 誤ったメールアドレスのユーザー情報を入力する
        // 3. ログインの処理を行う
        $response = $this->post('/admin/login', [
            'email' => 'wrong-' . $user->email,
            'password' => 'admin123',
        ]);

        // 期待挙動: 「ログイン情報が登録されていません」というバリデーションメッセージが表示される
        // 本番の AdminLoginController が投げる 'login_error' キーに合わせる
        $response->assertSessionHasErrors([
            'login_error' => 'ログイン情報が登録されていません'
        ]);
    }
}
