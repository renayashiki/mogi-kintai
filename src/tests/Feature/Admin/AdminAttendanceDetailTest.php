<?php

namespace Tests\Feature\Admin;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\User;
use App\Models\AttendanceRecord;
use App\Models\Rest;
use Carbon\Carbon;

class AdminAttendanceDetailTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Carbon::setTestNow('2026-02-16 10:00:00');
    }

    /**
     * ID:13 勤怠詳細情報 取得・修正機能(管理者)
     * 勤怠詳細画面に表示されるデータが選択したものになっている
     */
    public function test_admin_can_view_attendance_detail_correctly()
    {
        // 準備
        $admin = User::factory()->create(['admin_status' => 1]);
        $user = User::factory()->create(['name' => '西　怜奈']);
        $attendance = AttendanceRecord::factory()->create([
            'user_id' => $user->id,
            'date' => '2026-02-10',
            'clock_in' => '09:00:00',
            'clock_out' => '18:00:00',
            'comment' => '通常勤務',
        ]);
        Rest::create([
            'attendance_record_id' => $attendance->id,
            'rest_in' => '12:00:00',
            'rest_out' => '13:00:00',
        ]);
        /** @var User $admin */
        $this->actingAs($admin, 'admin');
        $response = $this->get(route('admin.attendance.detail', ['id' => $attendance->id]));
        $response->assertStatus(200);
        $response->assertSee('西　怜奈');
        $response->assertSee('2026年');
        $response->assertSee('2月10日');
        $response->assertSee('09:00');
        $response->assertSee('18:00');
        $response->assertSee('12:00');
        $response->assertSee('13:00');
        $response->assertSee('通常勤務');
    }

    /**
     * 出勤時間が退勤時間より後になっている場合、エラーメッセージが表示される
     */
    public function test_admin_update_fails_if_clock_in_after_clock_out()
    {
        $admin = User::factory()->create(['admin_status' => 1]);
        $attendance = AttendanceRecord::factory()->create(['date' => '2026-02-16']);
        /** @var User $admin */
        $this->actingAs($admin, 'admin');
        $response = $this->post(route('admin.attendance.update', ['id' => $attendance->id]), [
            'clock_in' => '18:00',
            'clock_out' => '09:00',
            'comment' => 'ミス',
        ]);
        $response->assertSessionHasErrors(['clock_out' => '出勤時間もしくは退勤時間が不適切な値です']);
    }

    /**
     * 休憩開始時間が退勤時間より後になっている場合、エラーメッセージが表示される
     */
    public function test_admin_update_fails_if_rest_in_after_clock_out()
    {
        $admin = User::factory()->create(['admin_status' => 1]);
        $attendance = AttendanceRecord::factory()->create(['date' => '2026-02-16']);
        /** @var User $admin */
        $this->actingAs($admin, 'admin');
        $response = $this->post(route('admin.attendance.update', ['id' => $attendance->id]), [
            'clock_in' => '09:00',
            'clock_out' => '18:00',
            'rests' => [
                ['in' => '19:00', 'out' => '19:30']
            ],
            'comment' => '休憩ミス',
        ]);
        $response->assertSessionHasErrors(['rests.0.in' => '休憩時間が不適切な値です']);
    }

    /**
     * 休憩終了時間が退勤時間より後になっている場合、エラーメッセージが表示される
     */
    public function test_admin_update_fails_if_rest_out_after_clock_out()
    {
        $admin = User::factory()->create(['admin_status' => 1]);
        $attendance = AttendanceRecord::factory()->create(['date' => '2026-02-16']);
        /** @var User $admin */
        $this->actingAs($admin, 'admin');
        $response = $this->post(route('admin.attendance.update', ['id' => $attendance->id]), [
            'clock_in' => '09:00',
            'clock_out' => '18:00',
            'rests' => [
                ['in' => '17:00', 'out' => '18:30']
            ],
            'comment' => '休憩ミス',
        ]);
        $response->assertSessionHasErrors(['rests.0.out' => '休憩時間もしくは退勤時間が不適切な値です']);
    }

    /**
     * 備考欄が未入力の場合、エラーメッセージが表示される
     */
    public function test_admin_update_fails_if_comment_is_empty()
    {
        $admin = User::factory()->create(['admin_status' => 1]);
        $attendance = AttendanceRecord::factory()->create();
        /** @var User $admin */
        $this->actingAs($admin, 'admin');
        $response = $this->post(route('admin.attendance.update', ['id' => $attendance->id]), [
            'clock_in' => '09:00',
            'clock_out' => '18:00',
            'comment' => '',
        ]);
        $response->assertSessionHasErrors(['comment' => '備考を記入してください']);
    }
}
