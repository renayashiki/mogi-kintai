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
        Notification::fake();
        $user = User::factory()->create([
            'email' => 'test-verify@example.com',
            'email_verified_at' => null,
        ]);
        event(new Registered($user));
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
        /** @var User $user */
        $response = $this->actingAs($user)->get(route('verification.notice'));
        $response->assertStatus(200);
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
        $verificationUrl = URL::temporarySignedRoute(
            'verification.verify',
            now()->addMinutes(60),
            ['id' => $user->id, 'hash' => sha1($user->email)]
        );
        /** @var User $user */
        $response = $this->actingAs($user)->get($verificationUrl);
        $response->assertRedirect(route('attendance.index'));
        $this->get(route('attendance.index'))->assertStatus(200);
        $this->assertNotNull($user->fresh()->email_verified_at);
    }
}
