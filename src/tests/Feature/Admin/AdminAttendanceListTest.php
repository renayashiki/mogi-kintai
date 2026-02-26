<?php

namespace Tests\Feature\Admin;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\User;
use App\Models\AttendanceRecord;
use App\Models\Rest;
use Carbon\Carbon;

class AdminAttendanceListTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Carbon::setTestNow('2026-02-16 10:00:00');
    }

    /**
     * ID:12 勤怠一覧情報 取得機能(管理者)
     * その日になされた全ユーザーの勤怠情報が正確に確認できる
     */
    public function test_admin_can_see_all_staff_attendance_accurately()
    {
        $admin = User::factory()->create(['admin_status' => 1]);
        $user1 = User::factory()->create(['name' => '西怜奈']);
        $user2 = User::factory()->create(['name' => '佐藤太郎']);
        $attendance1 = AttendanceRecord::factory()->create([
            'user_id' => $user1->id,
            'date' => '2026-02-16',
            'clock_in' => '09:00:15',
            'clock_out' => '18:00:45',
        ]);
        Rest::create([
            'attendance_record_id' => $attendance1->id,
            'rest_in' => '12:00:00',
            'rest_out' => '13:00:00',
        ]);
        $attendance2 = AttendanceRecord::factory()->create([
            'user_id' => $user2->id,
            'date' => '2026-02-16',
            'clock_in' => '10:00:00',
            'clock_out' => '19:00:00',
        ]);
        Rest::create([
            'attendance_record_id' => $attendance2->id,
            'rest_in' => '12:00:00',
            'rest_out' => '13:00:00',
        ]);
        /** @var User $admin */
        $this->actingAs($admin, 'admin');
        $response = $this->get(route('admin.attendance.list'));
        $response->assertStatus(200);
        $response->assertSee('2026年2月16日の勤怠');
        $response->assertSee('西怜奈');
        $response->assertSee('西怜奈');
        $response->assertSee('09:00');
        $response->assertSee('18:00');
        $response->assertSee('1:00');
        $response->assertSee('8:00');

        $response->assertSee('佐藤太郎');
        $response->assertSee('10:00');
        $response->assertSee('19:00');
        $response->assertSee('1:00');
        $response->assertSee('8:00');
        $response->assertDontSee('09:00:15');
    }


    /**
     * 遷移した際に現在の日付が表示される
     */
    public function test_admin_attendance_list_displays_current_date_initially()
    {
        /** @var User $admin */
        $admin = User::factory()->create(['admin_status' => 1]);
        $this->actingAs($admin, 'admin');
        $response = $this->get(route('admin.attendance.list'));
        $response->assertSee('2026年2月16日の勤怠');
        $response->assertSee('value="2026-02-16"', false);
    }

    /**
     * 「前日」を押下した時に前の日の勤怠情報が表示される
     */
    public function test_admin_can_navigate_to_previous_day_with_full_details()
    {
        $admin = User::factory()->create(['admin_status' => 1]);
        $user = User::factory()->create(['name' => '前日スタッフ']);
        $attendance = AttendanceRecord::factory()->create([
            'user_id' => $user->id,
            'date' => '2026-02-15',
            'clock_in' => '08:30:10',
            'clock_out' => '17:35:50',
        ]);
        Rest::create([
            'attendance_record_id' => $attendance->id,
            'rest_in' => '12:00:00',
            'rest_out' => '12:45:00',
        ]);
        /** @var User $admin */
        $this->actingAs($admin, 'admin');
        $response = $this->get(route('admin.attendance.list', ['date' => '2026-02-15']));
        $response->assertSee('2026年2月15日の勤怠');
        $response->assertSee('value="2026-02-15"', false);
        $response->assertSee('前日スタッフ');
        $response->assertSee('08:30');
        $response->assertSee('17:35');
        $response->assertSee('0:45');
        $response->assertSee('8:20');
    }

    /**
     * 「翌日」を押下した時に次の日の勤怠情報が表示される
     */
    public function test_admin_can_navigate_to_next_day_with_full_details()
    {
        $admin = User::factory()->create(['admin_status' => 1]);
        $user = User::factory()->create(['name' => '翌日スタッフ']);
        $attendance = AttendanceRecord::factory()->create([
            'user_id' => $user->id,
            'date' => '2026-02-17',
            'clock_in' => '10:00:00',
            'clock_out' => '19:00:00',
        ]);
        Rest::create([
            'attendance_record_id' => $attendance->id,
            'rest_in' => '13:00:00',
            'rest_out' => '14:30:00',
        ]);
        /** @var User $admin */
        $this->actingAs($admin, 'admin');
        $response = $this->get(route('admin.attendance.list', ['date' => '2026-02-17']));
        $response->assertSee('2026年2月17日の勤怠');
        $response->assertSee('value="2026-02-17"', false);
        $response->assertSee('翌日スタッフ');
        $response->assertSee('10:00');
        $response->assertSee('19:00');
        $response->assertSee('1:30');
        $response->assertSee('7:30');
    }
}
