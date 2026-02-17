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
        // 西さんのデータ（秒あり）
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

        // 佐藤さんのデータ（別の時間帯）
        $attendance2 = AttendanceRecord::factory()->create([
            'user_id' => $user2->id,
            'date' => '2026-02-16',
            'clock_in' => '10:00:00',
            'clock_out' => '19:00:00',
        ]);


        // 休憩1時間（正確に保存）
        Rest::create([
            'attendance_record_id' => $attendance2->id,
            'rest_in' => '12:00:00',
            'rest_out' => '13:00:00',
        ]);

        /** @var User $admin */
        $this->actingAs($admin, 'admin');

        $response = $this->get(route('admin.attendance.list'));
        $response->assertStatus(200);

        // --- UI画像に基づいた全項目の正確な値の確認 ---
        $response->assertSee('2026年2月16日の勤怠'); // 日付
        $response->assertSee('西怜奈');               // 名前
        $response->assertSee('西怜奈');
        $response->assertSee('09:00'); // 出勤（09:00:15の秒切り捨て）
        $response->assertSee('18:00'); // 退勤（18:00:45の秒切り捨て）
        $response->assertSee('1:00');  // 休憩（アクセサ：1:00形式）
        $response->assertSee('8:00');  // 合計（アクセサ：8:00形式）

        // 2人目：佐藤太郎さんの検証（「全ユーザー」が表示されていることの証明）
        $response->assertSee('佐藤太郎');
        $response->assertSee('10:00'); // 出勤
        $response->assertSee('19:00'); // 退勤
        $response->assertSee('1:00');  // 休憩
        $response->assertSee('8:00');  // 合計

        // --- 共通の禁止事項確認 ---
        $response->assertDontSee('09:00:15'); // DBの秒が漏れ出していないか確認
    }


    /**
     * 遷移した際に現在の日付が表示される
     */
    public function test_admin_attendance_list_displays_current_date_initially()
    {
        /** @var User $admin */
        $admin = User::factory()->create(['admin_status' => 1]);
        $this->actingAs($admin, 'admin');

        // 1. 勤怠一覧画面を開く
        $response = $this->get(route('admin.attendance.list'));

        // 期待挙動：勤怠一覧画面にその日の日付が表示されている
        $response->assertSee('2026年2月16日の勤怠'); // タイトル
        $response->assertSee('2026/02/16');         // カレンダー部分
    }

    /**
     * 「前日」を押下した時に前の日の勤怠情報が表示される
     */
    public function test_admin_can_navigate_to_previous_day_with_full_details()
    {
        $admin = User::factory()->create(['admin_status' => 1]);
        $user = User::factory()->create(['name' => '前日スタッフ']);

        // 2026-02-15 (前日) の勤怠
        $attendance = AttendanceRecord::factory()->create([
            'user_id' => $user->id,
            'date' => '2026-02-15',
            'clock_in' => '08:30:10',
            'clock_out' => '17:35:50',
        ]);
        Rest::create([
            'attendance_record_id' => $attendance->id,
            'rest_in' => '12:00:00',
            'rest_out' => '12:45:00', // 45分休憩
        ]);

        /** @var User $admin */
        $this->actingAs($admin, 'admin');

        // 前日のURLへリクエスト
        $response = $this->get(route('admin.attendance.list', ['date' => '2026-02-15']));

        // --- 全項目の正確性確認 ---
        $response->assertSee('2026年2月15日の勤怠');
        $response->assertSee('前日スタッフ');
        $response->assertSee('08:30'); // 出勤
        $response->assertSee('17:35'); // 退勤
        $response->assertSee('0:45');  // 休憩 (45分)
        $response->assertSee('8:20');  // 合計 (9:05 - 0:45 = 8:20)
    }

    /**
     * 「翌日」を押下した時に次の日の勤怠情報が表示される
     */
    public function test_admin_can_navigate_to_next_day_with_full_details()
    {
        $admin = User::factory()->create(['admin_status' => 1]);
        $user = User::factory()->create(['name' => '翌日スタッフ']);

        // 2026-02-17 (翌日) の勤怠
        $attendance = AttendanceRecord::factory()->create([
            'user_id' => $user->id,
            'date' => '2026-02-17',
            'clock_in' => '10:00:00',
            'clock_out' => '19:00:00',
        ]);
        Rest::create([
            'attendance_record_id' => $attendance->id,
            'rest_in' => '13:00:00',
            'rest_out' => '14:30:00', // 1時間30分休憩
        ]);

        /** @var User $admin */
        $this->actingAs($admin, 'admin');

        // 翌日のURLへリクエスト
        $response = $this->get(route('admin.attendance.list', ['date' => '2026-02-17']));

        // --- 全項目の正確性確認 ---
        $response->assertSee('2026年2月17日の勤怠');
        $response->assertSee('翌日スタッフ');
        $response->assertSee('10:00'); // 出勤
        $response->assertSee('19:00'); // 退勤
        $response->assertSee('1:30');  // 休憩
        $response->assertSee('7:30');  // 合計 (9:00 - 1:30 = 7:30)
    }
}
