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
        $userData = User::factory()->raw([
            'name' => '',
        ]);
        $response = $this->post('/register', $userData);
        $response->assertSessionHasErrors([
            'name' => 'お名前を入力してください'
        ]);
    }

    /**
     * メールアドレスが未入力の場合、バリデーションメッセージが表示される
     */
    public function test_email_is_required()
    {
        $userData = User::factory()->raw([
            'email' => '',
        ]);
        $response = $this->post('/register', $userData);
        $response->assertSessionHasErrors([
            'email' => 'メールアドレスを入力してください'
        ]);
    }

    /**
     * パスワードが8文字未満の場合、バリデーションメッセージが表示される
     */
    public function test_password_is_too_short()
    {
        $userData = User::factory()->raw([
            'password' => 'short',
            'password_confirmation' => 'short',
        ]);
        $response = $this->post('/register', $userData);
        $response->assertSessionHasErrors([
            'password' => 'パスワードは8文字以上で入力してください'
        ]);
    }

    /**
     * パスワードが一致しない場合、バリデーションメッセージが表示される
     */
    public function test_password_confirmation_does_not_match()
    {
        $userData = User::factory()->raw([
            'password' => 'password123',
            'password_confirmation' => 'different_password',
        ]);
        $response = $this->post('/register', $userData);
        $response->assertSessionHasErrors([
            'password_confirmation' => 'パスワードと一致しません'
        ]);
    }

    /**
     * パスワードが未入力の場合、バリデーションメッセージが表示される
     */
    public function test_password_is_required()
    {
        $userData = User::factory()->raw([
            'password' => '',
            'password_confirmation' => '',
        ]);
        $response = $this->post('/register', $userData);
        $response->assertSessionHasErrors([
            'password' => 'パスワードを入力してください'
        ]);
    }

    /**
     * フォームに内容が入力されていた場合、データが正常に保存される
     */
    public function test_registration_success()
    {
        $password = 'password123';
        $userData = User::factory()->raw([
            'password' => $password,
            'password_confirmation' => $password,
        ]);
        $response = $this->post('/register', $userData);
        $this->assertDatabaseHas('users', [
            'name' => $userData['name'],
            'email' => $userData['email'],
        ]);
        $user = User::where('email', $userData['email'])->first();
        $this->assertTrue(Hash::check($password, $user->password));
        $this->assertAuthenticatedAs($user);
        $response->assertRedirect(route('verification.notice'));
    }
}
