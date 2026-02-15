<?php

namespace Tests\Feature\Auth;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class RegistrationTest extends TestCase
{
    use RefreshDatabase;

    /**
     * ID 1: 認証機能（一般ユーザー）
     * 名前が未入力の場合、バリデーションメッセージが表示される
     */
    public function test_name_is_required()
    {
        // 1. 名前以外のユーザー情報を入力する
        $userData = User::factory()->raw([
            'name' => '', // 名前を未入力にする
        ]);

        // 2. 会員登録の処理を行う
        $response = $this->post('/register', $userData);

        // 期待挙動: 「お名前を入力してください」というバリデーションメッセージが表示される
        $response->assertSessionHasErrors([
            'name' => 'お名前を入力してください'
        ]);
    }

    /**
     * メールアドレスが未入力の場合、バリデーションメッセージが表示される
     */
    public function test_email_is_required()
    {
        // 1. メールアドレス以外のユーザー情報を入力する
        $userData = User::factory()->raw([
            'email' => '', // メールアドレスを未入力にする
        ]);

        // 2. 会員登録の処理を行う
        $response = $this->post('/register', $userData);

        // 期待挙動: 「メールアドレスを入力してください」というバリデーションメッセージが表示される
        $response->assertSessionHasErrors([
            'email' => 'メールアドレスを入力してください'
        ]);
    }

    /**
     * パスワードが8文字未満の場合、バリデーションメッセージが表示される
     */
    public function test_password_is_too_short()
    {
        // 1. パスワードを8文字未満にし、ユーザー情報を入力する
        $userData = User::factory()->raw([
            'password' => 'short', // 8文字未満にする
            'password_confirmation' => 'short',
        ]);

        // 2. 会員登録の処理を行う
        $response = $this->post('/register', $userData);

        // 期待挙動: 「パスワードは8文字以上で入力してください」というバリデーションメッセージが表示される
        $response->assertSessionHasErrors([
            'password' => 'パスワードは8文字以上で入力してください'
        ]);
    }

    /**
     * パスワードが一致しない場合、バリデーションメッセージが表示される
     */
    public function test_password_confirmation_does_not_match()
    {
        // 1. 確認用のパスワードとパスワードを一致させず、ユーザー情報を入力する
        $userData = User::factory()->raw([
            'password' => 'password123',
            'password_confirmation' => 'different_password', // 一致させない
        ]);

        // 2. 会員登録の処理を行う
        $response = $this->post('/register', $userData);

        // 期待挙動: 「パスワードと一致しません」というバリデーションメッセージが表示される
        $response->assertSessionHasErrors([
            'password_confirmation' => 'パスワードと一致しません'
        ]);
    }

    /**
     * パスワードが未入力の場合、バリデーションメッセージが表示される
     */
    public function test_password_is_required()
    {
        // 1. パスワード以外のユーザー情報を入力する
        $userData = User::factory()->raw([
            'password' => '', // 未入力にする
            'password_confirmation' => '',
        ]);

        // 2. 会員登録の処理を行う
        $response = $this->post('/register', $userData);

        // 期待挙動: 「パスワードを入力してください」というバリデーションメッセージが表示される
        $response->assertSessionHasErrors([
            'password' => 'パスワードを入力してください'
        ]);
    }

    /**
     * フォームに内容が入力されていた場合、データが正常に保存される
     */
    public function test_registration_success()
    {
        // 1. ユーザー情報を入力する
        $password = 'password123';
        $userData = User::factory()->raw([
            'password' => $password,
            'password_confirmation' => $password,
        ]);

        // 2. 会員登録の処理を行う
        $response = $this->post('/register', $userData);

        // 期待挙動: データベースに登録したユーザー情報が保存される
        $this->assertDatabaseHas('users', [
            'name' => $userData['name'],
            'email' => $userData['email'],
        ]);

        // パスワードがハッシュ化されて正しく保存されているかを確認
        $user = User::where('email', $userData['email'])->first();
        $this->assertTrue(Hash::check($password, $user->password));

        // ログイン状態とリダイレクトの確認
        $this->assertAuthenticatedAs($user);
        $response->assertRedirect(route('verification.notice'));
    }
}
