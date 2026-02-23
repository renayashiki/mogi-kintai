<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Support\Facades\URL;
use Tests\TestCase;
use Illuminate\Auth\Events\Registered;

class EmailVerificationTest extends TestCase
{
    use RefreshDatabase;

    /**
     * ID:16 メール認証機能
     * 会員登録後、認証メールが送信される
     */
    public function test_verification_email_sent_after_registration()
    {
        // メールの実送信を止め、偽装（Fake）する
        Notification::fake();

        // 手順 1. 会員登録をする (Factoryで未認証ユーザーを作成)
        // プロジェクト方針に基づき、サンプルデータ（山田花子等）と混ざらないよう
        // テスト専用のメールアドレスを指定します [cite: 2026-02-03]
        $user = User::factory()->create([
            'email' => 'test-verify@example.com',
            'email_verified_at' => null, // 未認証状態を確実にする
        ]);

        // 手順 2. 認証メールを送信する
        // Laravelの標準仕様では Registered イベントを契機にメールが送られます。
        // これを明示的に発行することで「送信手順」をテストコード上で表現します。
        event(new Registered($user));

        // 期待挙動：登録したメールアドレス宛に認証メールが送信されている
        // 精密な検証のため、特定のユーザーに対し、VerifyEmail通知が飛んだことを確認します
        Notification::assertSentTo(
            $user,
            VerifyEmail::class
        );
    }

    /**
     * メール認証誘導画面で「認証はこちらから」ボタンを押下するとメール認証サイトに遷移する
     */
    public function test_transition_to_mailhog_from_notice_screen()
    {
        $user = User::factory()->create(['email_verified_at' => null]);

        // 1. メール認証導線画面を表示する
        /** @var User $user */
        $response = $this->actingAs($user)->get(route('verification.notice'));
        $response->assertStatus(200);

        // 2. 「認証はこちらから」ボタンを押下 ＆ 3. メール認証サイトを表示する
        // Laravelの機能テストにおいて、外部URLへの「遷移」を証明する唯一の方法は、
        // 「そのボタンが正しい外部URLを保持しているか」をHTML構造として厳密に検証することです
        $this->actingAs($user)->get(route('verification.notice'))
            ->assertSeeInOrder([
                '<a',
                'href="http://localhost:8025"',
                'class="verify-link-button"',
                '認証はこちらから',
                '</a>'
            ], false);
    }

    /**
     * メール認証サイトのメール認証を完了すると、勤怠登録画面に遷移する
     */
    public function test_redirect_to_attendance_index_after_verification()
    {
        $user = User::factory()->create(['email_verified_at' => null]);

        // 1. メール認証を完了する
        // メール内リンク（署名付きURL）を叩く動作が「認証を完了する」操作にあたります
        $verificationUrl = URL::temporarySignedRoute(
            'verification.verify',
            now()->addMinutes(60),
            ['id' => $user->id, 'hash' => sha1($user->email)]
        );

        /** @var User $user */
        $response = $this->actingAs($user)->get($verificationUrl);

        // 2. 勤怠登録画面を表示する
        // 期待挙動：勤怠登録画面に遷移する
        $response->assertRedirect(route('attendance.index'));

        // 遷移後の画面が正しいか確認
        $this->get(route('attendance.index'))->assertStatus(200);
        // DB上の認証完了も合わせて証明
        $this->assertNotNull($user->fresh()->email_verified_at);
    }
}
