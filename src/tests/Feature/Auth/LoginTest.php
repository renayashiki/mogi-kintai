<?php

namespace Tests\Feature\Auth;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\User;

class LoginTest extends TestCase
{
    use RefreshDatabase;

    /**
     * ID 2: ログイン認証機能（一般ユーザー）
     * メールアドレスが未入力の場合、バリデーションメッセージが表示される
     */
    public function test_email_is_required()
    {
        // 1. ユーザーを登録する
        // ログイン試行に使うパスワードだけ固定して作成
        User::factory()->create([
            'password' => bcrypt('password123'),
        ]);

        // 2. メールアドレス以外のユーザー情報を入力する
        // 3. ログインの処理を行う
        $response = $this->post('/login', [
            'email' => '', // 手順通り、ここを空にする
            'password' => 'password123',
        ]);

        // 期待挙動: 「メールアドレスを入力してください」というバリデーションメッセージが表示される
        $response->assertSessionHasErrors([
            'email' => 'メールアドレスを入力してください'
        ]);
    }

    /**
     * パスワードが未入力の場合、バリデーションメッセージが表示される
     */
    public function test_password_is_required()
    {
        // 1. ユーザーを登録する
        $user = User::factory()->create();

        // 2. パスワード以外のユーザー情報を入力する
        // 3. ログインの処理を行う
        $response = $this->post('/login', [
            'email' => $user->email, // Factoryが生成したランダムなemailを注入
            'password' => '',        // 手順通り、ここを空にする
        ]);

        // 期待挙動: 「パスワードを入力してください」というバリデーションメッセージが表示される
        $response->assertSessionHasErrors([
            'password' => 'パスワードを入力してください'
        ]);
    }

    /**
     * 登録内容と一致しない場合、バリデーションメッセージが表示される
     */
    public function test_login_fails_with_invalid_credentials()
    {
        // 1. ユーザーを登録する
        $user = User::factory()->create([
            'password' => bcrypt('password123'),
        ]);

        // 2. 誤ったメールアドレスのユーザー情報を入力する
        // 3. ログインの処理を行う
        $response = $this->post('/login', [
            'email' => 'wrong-' . $user->email, // Factoryが作ったアドレスをわざと加工して「存在しない」状態に
            'password' => 'password123',
        ]);

        // 期待挙動: 「ログイン情報が登録されていません」というバリデーションメッセージが表示される
        $response->assertSessionHasErrors([
            'login_error' => 'ログイン情報が登録されていません'
        ]);
    }
}
