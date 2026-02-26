<?php

namespace Tests\Feature\User;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\User;
use App\Models\AttendanceRecord;
use App\Models\AttendanceCorrect;


class AttendanceCorrectionTest extends TestCase
{
    use RefreshDatabase;

    /**
     * ID:11 勤怠詳細情報 修正機能
     * 出勤時間が退勤時間より後になっている場合、エラーメッセージが表示される
     */
    public function test_error_when_clock_in_is_after_clock_out()
    {
        $user = User::factory()->create();
        $attendance = AttendanceRecord::factory()->create([
            'user_id' => $user->id,
            'clock_in' => '09:00:00',
            'clock_out' => '18:00:00',
        ]);
        /** @var User $user */
        $this->actingAs($user);
        $response = $this->get(route('attendance.detail', ['id' => $attendance->id]));
        $response->assertStatus(200);
        $response = $this->post(route('attendance.update', ['id' => $attendance->id]), [
            'clock_in' => '20:00',
            'clock_out' => '18:00',
            'comment' => '修正理由',
        ]);
        $response->assertSessionHasErrors(['clock_out']);
        $this->get(route('attendance.detail', ['id' => $attendance->id]))
            ->assertSee('出勤時間もしくは退勤時間が不適切な値です');
        $this->assertDatabaseHas('attendance_records', [
            'id' => $attendance->id,
            'clock_in' => '09:00:00',
            'clock_out' => '18:00:00',
        ]);
    }

    /**
     * 休憩開始時間が退勤時間より後になっている場合、エラーメッセージが表示される
     */
    public function test_error_when_rest_in_is_after_clock_out()
    {
        $user = User::factory()->create();
        $attendance = AttendanceRecord::factory()->create(['user_id' => $user->id]);
        /** @var User $user */
        $this->actingAs($user);
        $this->get(route('attendance.detail', ['id' => $attendance->id]));
        $response = $this->post(route('attendance.update', ['id' => $attendance->id]), [
            'clock_in' => '09:00',
            'clock_out' => '18:00',
            'rests' => [['in' => '19:00', 'out' => '19:30']],
            'comment' => '修正理由',
        ]);
        $response->assertSessionHasErrors(['rests.0.in']);
        $this->get(route('attendance.detail', ['id' => $attendance->id]))
            ->assertSee('休憩時間が不適切な値です');
        $this->assertDatabaseMissing('rests', [
            'attendance_record_id' => $attendance->id,
            'rest_in' => '19:00:00',
        ]);
    }

    /**
     * 休憩終了時間が退勤時間より後になっている場合、エラーメッセージが表示される
     */
    public function test_error_when_rest_out_is_after_clock_out()
    {
        $user = User::factory()->create();
        $attendance = AttendanceRecord::factory()->create(['user_id' => $user->id]);
        /** @var User $user */
        $this->actingAs($user);
        $this->get(route('attendance.detail', ['id' => $attendance->id]));
        $response = $this->post(route('attendance.update', ['id' => $attendance->id]), [
            'clock_in' => '09:00',
            'clock_out' => '18:00',
            'rests' => [['in' => '12:00', 'out' => '19:00']],
            'comment' => '修正理由',
        ]);
        $response->assertSessionHasErrors(['rests.0.out']);
        $this->get(route('attendance.detail', ['id' => $attendance->id]))
            ->assertSee('休憩時間もしくは退勤時間が不適切な値です');
        $this->assertDatabaseMissing('rests', [
            'attendance_record_id' => $attendance->id,
            'rest_out' => '19:00:00',
        ]);
    }

    /**
     * 備考欄が未入力の場合、エラーメッセージが表示される
     */
    public function test_error_when_comment_is_empty()
    {
        $user = User::factory()->create();
        $attendance = AttendanceRecord::factory()->create(['user_id' => $user->id]);
        /** @var User $user */
        $this->actingAs($user);
        $this->get(route('attendance.detail', ['id' => $attendance->id]));
        $response = $this->post(route('attendance.update', ['id' => $attendance->id]), [
            'clock_in' => '09:00',
            'clock_out' => '18:00',
            'comment' => '',
        ]);
        $response->assertSessionHasErrors(['comment']);
        $this->get(route('attendance.detail', ['id' => $attendance->id]))
            ->assertSee('備考を記入してください');
    }

    /**
     * 修正申請処理が実行される
     */
    public function test_correction_request_and_admin_view_full_match()
    {
        $user = User::factory()->create(['name' => '西怜奈', 'admin_status' => 0]);
        $admin = User::factory()->create(['admin_status' => 1]);
        $attendance = AttendanceRecord::factory()->create([
            'user_id' => $user->id,
            'date' => '2023-06-01'
        ]);
        /** @var User $user */
        $this->actingAs($user);
        $response = $this->post(route('attendance.update', ['id' => $attendance->id]), [
            'clock_in' => '10:00',
            'clock_out' => '19:00',
            'rests' => [
                ['in' => '12:00', 'out' => '13:00'],
                ['in' => '15:00', 'out' => '15:30'],
            ],
            'comment' => '電車遅延のため',
        ]);
        /** @var User $admin */
        $this->actingAs($admin, 'admin');
        $response = $this->get(route('attendance.request.list', ['status' => 'pending']));
        $response->assertStatus(200);
        $response->assertSee('承認待ち');
        $response->assertSee('西怜奈');
        $response->assertSee('2023/06/01');
        $response->assertSee('電車遅延のため');
        $response->assertSee(now()->format('Y/m/d'));

        $request = AttendanceCorrect::where('user_id', $user->id)->latest('id')->first();
        $this->assertEquals(0, $request->status);
        $response = $this->get(route('admin.request.approve', ['attendance_correct_request_id' => $request->id]));
        $response->assertStatus(200);
        $response->assertSee('西怜奈');
        $response->assertSee('2023年');
        $response->assertSee('6月1日');
        $response->assertSee('10:00');
        $response->assertSee('19:00');
        $response->assertSee('12:00');
        $response->assertSee('13:00');
        $response->assertSee('15:00');
        $response->assertSee('15:30');
        $response->assertSee('電車遅延のため');
    }

    /**
     * 「承認待ち」にログインユーザーが行った申請が全て表示されている
     */
    public function test_user_can_see_own_pending_requests_with_full_details()
    {
        $user = User::factory()->create(['name' => '西怜奈']);
        $date = '2023-06-01';
        $attendance = AttendanceRecord::factory()->create(['user_id' => $user->id, 'date' => $date]);
        /** @var User $user */
        $this->actingAs($user);
        $this->post(route('attendance.update', ['id' => $attendance->id]), [
            'clock_in' => '08:00',
            'clock_out' => '17:00',
            'rests' => [
                ['in' => '12:00', 'out' => '13:00'],
                ['in' => null, 'out' => null],
            ],
            'comment' => '自分で行った申請',
        ]);
        $response = $this->get(route('attendance.request.list', ['status' => 'pending']));
        $response->assertStatus(200);
        $response->assertSee('承認待ち');
        $response->assertSee('西怜奈');
        $response->assertSee('2023/06/01');
        $response->assertSee('自分で行った申請');
        $response->assertSee(now()->format('Y/m/d'));
    }

    /**
     * 「承認済み」に管理者が承認した修正申請が全て表示されている
     */
    public function test_approved_requests_display_full_details_after_approval()
    {
        $user = User::factory()->create(['name' => '西怜奈', 'admin_status' => 0]);
        $admin = User::factory()->create(['admin_status' => 1]);
        $attendance = AttendanceRecord::factory()->create(['user_id' => $user->id, 'date' => '2023-06-01']);
        /** @var User $user */
        $this->actingAs($user);
        $this->post(route('attendance.update', ['id' => $attendance->id]), [
            'clock_in' => '10:00',
            'clock_out' => '19:00',
            'rests' => [
                ['in' => '12:00', 'out' => '13:00'],
                ['in' => '15:00', 'out' => '16:00'],
            ],
            'comment' => '承認後データ確認用',
        ]);
        $request = AttendanceCorrect::where('user_id', $user->id)
            ->where('comment', '承認後データ確認用')
            ->latest('id')
            ->first();
        /** @var User $admin */
        $this->actingAs($admin, 'admin');
        $this->post(route('admin.attendance.approve', ['attendance_correct_request_id' => $request->id]));
        $this->actingAs($user);
        $response = $this->get(route('attendance.request.list', ['status' => 'approved']));
        $response->assertStatus(200);
        $response->assertSee('承認済み');
        $response->assertSee('西怜奈');
        $response->assertSee('2023/06/01');
        $response->assertSee('承認後データ確認用');
        $response->assertSee(now()->format('Y/m/d'));
    }

    /**
     * 各申請の「詳細」を押下すると、勤怠詳細画面に遷移する
     */
    public function test_detail_button_redirects_with_proper_workflow()
    {
        /** @var User $user */
        $user = User::factory()->create(['name' => '西怜奈']);
        $date = '2023-06-01';
        $attendance = AttendanceRecord::factory()->create([
            'user_id' => $user->id,
            'date' => $date
        ]);
        $this->actingAs($user);
        $this->post(route('attendance.update', ['id' => $attendance->id]), [
            'clock_in' => '09:00',
            'clock_out' => '18:00',
            'rests' => [
                ['in' => '12:00', 'out' => '13:00'],
                ['in' => '15:00', 'out' => '15:15']
            ],
            'comment' => '詳細遷移テスト',
        ]);
        $response = $this->get(route('attendance.request.list', ['status' => 'pending']));
        $detailUrl = route('attendance.detail', ['id' => $attendance->id]);
        $response->assertSee($detailUrl);
        $response = $this->get($detailUrl);
        $response->assertStatus(200);
        $response->assertSee('勤怠詳細');
        $response->assertSee('西怜奈');
        $response->assertSee('2023年');
        $response->assertSee('6月1日');
        $response->assertSee('09:00');
        $response->assertSee('18:00');
        $response->assertSee('12:00');
        $response->assertSee('13:00');
        $response->assertSee('15:00');
        $response->assertSee('15:15');
        $response->assertSee('詳細遷移テスト');
    }
}
