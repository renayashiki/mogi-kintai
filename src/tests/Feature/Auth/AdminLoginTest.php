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
        User::factory()->create([
            'password' => bcrypt('admin123'),
        ]);
        $response = $this->post('/admin/login', [
            'email' => '',
            'password' => 'admin123',
        ]);
        $response->assertSessionHasErrors([
            'email' => 'メールアドレスを入力してください'
        ]);
    }

    /**
     * パスワードが未入力の場合、バリデーションメッセージが表示される
     */
    public function test_admin_password_is_required()
    {
        $user = User::factory()->create();
        $response = $this->post('/admin/login', [
            'email' => $user->email,
            'password' => '',
        ]);
        $response->assertSessionHasErrors([
            'password' => 'パスワードを入力してください'
        ]);
    }

    /**
     * 登録内容と一致しない場合、バリデーションメッセージが表示される
     */
    public function test_admin_login_fails_with_invalid_credentials()
    {
        $user = User::factory()->create([
            'password' => bcrypt('admin123'),
        ]);
        $response = $this->post('/admin/login', [
            'email' => 'wrong-' . $user->email,
            'password' => 'admin123',
        ]);
        $response->assertSessionHasErrors([
            'login_error' => 'ログイン情報が登録されていません'
        ]);
    }
}
