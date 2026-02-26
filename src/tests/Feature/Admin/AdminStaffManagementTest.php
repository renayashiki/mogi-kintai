<?php

namespace Tests\Feature\Admin;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\User;
use App\Models\AttendanceRecord;
use App\Models\Rest;
use Carbon\Carbon;

class AdminStaffManagementTest extends TestCase
{
    use RefreshDatabase;

    /**
     * ID:14 ユーザー情報取得機能(管理者)
     * 管理者ユーザーが、全一般ユーザーの氏名とメールアドレスを確認できる
     */
    public function test_admin_can_view_all_staff_info()
    {
        $admin = User::factory()->create(['admin_status' => 1]);
        $users = User::factory()->count(3)->create(['admin_status' => 0]);
        /** @var User $admin */
        $response = $this->actingAs($admin, 'admin')
            ->get(route('staff.list'));
        $response->assertStatus(200);
        foreach ($users as $user) {
            $response->assertSee($user->name);
            $response->assertSee($user->email);
        }
        $response->assertDontSee($admin->email);
    }

    /**
     * ユーザーの勤怠情報が正しく表示される
     */
    public function test_admin_can_view_selected_user_attendance_correctly()
    {
        $admin = User::factory()->create(['admin_status' => 1]);
        $user = User::factory()->create(['name' => '西 怜奈', 'admin_status' => 0]);
        $date = Carbon::now();
        $attendance = AttendanceRecord::create([
            'user_id' => $user->id,
            'date' => $date->format('Y-m-d'),
            'clock_in' => '09:00:00',
            'clock_out' => '18:00:00',
        ]);
        Rest::create([
            'attendance_record_id' => $attendance->id,
            'rest_in' => '12:00:00',
            'rest_out' => '13:00:00',
        ]);
        /** @var User $admin */
        $response = $this->actingAs($admin, 'admin')
            ->get(route('staff.log', ['id' => $user->id]));
        $response->assertStatus(200);
        $response->assertSee($user->name . 'さんの勤怠');
        $response->assertSee($date->format('m/d'));
        $response->assertSee('09:00');
        $response->assertSee('18:00');
        $response->assertSee('1:00');
        $response->assertSee('8:00');
    }

    /**
     * 「前月」を押下した時に表示月の前月の情報が表示される
     */
    public function test_admin_can_view_previous_month_attendance_list()
    {
        $admin = User::factory()->create(['admin_status' => 1]);
        $user = User::factory()->create(['admin_status' => 0]);
        $prevMonth = Carbon::now()->subMonth();
        $attendances = [];
        for ($i = 1; $i <= 10; $i++) {
            $date = $prevMonth->copy()->day($i)->format('Y-m-d');
            $attendance = AttendanceRecord::create([
                'user_id'  => $user->id,
                'date'     => $date,
                'clock_in' => '09:00:00',
                'clock_out' => '18:00:00',
            ]);
            Rest::create([
                'attendance_record_id' => $attendance->id,
                'rest_in'  => '12:00:00',
                'rest_out' => '13:00:00',
            ]);
            $attendances[] = $date;
        }
        /** @var User $admin */
        $response = $this->actingAs($admin, 'admin')
            ->get(route('staff.log', [
                'id' => $user->id,
                'month' => $prevMonth->format('Y-m')
            ]));
        $response->assertStatus(200);
        $response->assertSee($prevMonth->format('Y/m'));
        foreach ($attendances as $dateStr) {
            $formattedDate = Carbon::parse($dateStr)->format('m/d');
            $response->assertSee($formattedDate);
            $response->assertSee('09:00');
            $response->assertSee('18:00');
            $response->assertSee('1:00');
            $response->assertSee('8:00');
        }
    }

    /**
     * 「翌月」を押下した時に表示月の翌月の情報が表示される
     */
    public function test_admin_can_view_next_month_attendance_list()
    {
        $admin = User::factory()->create(['admin_status' => 1]);
        $user = User::factory()->create(['admin_status' => 0]);
        $nextMonth = Carbon::now()->addMonth();
        $attendances = [];
        for ($i = 1; $i <= 15; $i++) {
            $date = $nextMonth->copy()->day($i)->format('Y-m-d');
            $attendance = AttendanceRecord::create([
                'user_id'  => $user->id,
                'date'     => $date,
                'clock_in' => '10:00:00',
                'clock_out' => '19:00:00',
            ]);
            Rest::create([
                'attendance_record_id' => $attendance->id,
                'rest_in'  => '12:00:00',
                'rest_out' => '13:00:00',
            ]);
            $attendances[] = $date;
        }
        /** @var User $admin */
        $response = $this->actingAs($admin, 'admin')
            ->get(route('staff.log', [
                'id' => $user->id,
                'month' => $nextMonth->format('Y-m')
            ]));
        $response->assertStatus(200);
        $response->assertSee($nextMonth->format('Y/m'));
        foreach ($attendances as $dateStr) {
            $formattedDate = Carbon::parse($dateStr)->format('m/d');
            $response->assertSee($formattedDate);
            $response->assertSee('10:00');
            $response->assertSee('19:00');
            $response->assertSee('1:00');
            $response->assertSee('8:00');
        }
    }

    /**
     * 「詳細」を押下すると、その日の勤怠詳細画面に遷移する
     */
    public function test_admin_can_navigate_to_attendance_detail_and_data_matches()
    {
        $admin = User::factory()->create(['admin_status' => 1]);
        $user = User::factory()->create(['name' => '西 怜奈']);
        $attendance = AttendanceRecord::create([
            'user_id' => $user->id,
            'date' => '2026-02-10',
            'clock_in' => '09:15:00',
            'clock_out' => '18:15:00',
        ]);
        Rest::create([
            'attendance_record_id' => $attendance->id,
            'rest_in' => '12:30:00',
            'rest_out' => '13:30:00',
        ]);
        /** @var User $admin */
        $this->actingAs($admin, 'admin');
        $listResponse = $this->get(route('staff.log', ['id' => $user->id]));
        $listResponse->assertStatus(200);
        $detailUrl = route('admin.attendance.detail', ['id' => $attendance->id]);
        $listResponse->assertSee($detailUrl);
        $detailResponse = $this->get($detailUrl);
        $detailResponse->assertStatus(200);
        $detailResponse->assertSee('勤怠詳細');
        $detailResponse->assertSee('西 怜奈');
        $detailResponse->assertSee('2026年');
        $detailResponse->assertSee('2月10日');
        $detailResponse->assertSee('09:15');
        $detailResponse->assertSee('18:15');
        $detailResponse->assertSee('12:30');
        $detailResponse->assertSee('13:30');
    }
}
