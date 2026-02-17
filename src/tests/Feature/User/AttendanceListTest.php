<?php

namespace Tests\Feature\User;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\User;
use App\Models\AttendanceRecord;
use App\Models\Rest;
use Carbon\Carbon;

class AttendanceListTest extends TestCase
{
    use RefreshDatabase;

    protected $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
    }

    /**
     * ID:9 勤怠一覧情報 取得機能
     * 1. 自分が行った勤怠情報が全て表示されている
     */
    public function test_user_can_see_own_attendance_list()
    {
        Carbon::setTestNow('2026-02-15');

        // 1. 勤怠情報を登録
        $attendance = AttendanceRecord::create([
            'user_id' => $this->user->id,
            'date' => '2026-02-15',
            'clock_in' => '2026-02-15 09:00:00',
            'clock_out' => '2026-02-15 18:00:00',
        ]);
        Rest::create([
            'attendance_record_id' => $attendance->id,
            'rest_in' => '2026-02-15 12:00:00',
            'rest_out' => '2026-02-15 13:00:00',
        ]);

        // 2. ログインする（手順通り登録後にログイン）
        $this->actingAs($this->user);

        // 3. 勤怠一覧ページを開く
        $response = $this->get(route('attendance.list'));

        // 【期待挙動】日付・出勤・退勤・休憩・合計の5項目を確認
        $response->assertStatus(200);
        $response->assertSee('02/15');
        $response->assertSee('09:00');
        $response->assertSee('18:00');
        $response->assertSee('1:00');
        $response->assertSee('8:00');
    }

    /**
     * 2. 勤怠一覧画面に遷移した際に現在の月が表示される
     */
    public function test_attendance_list_shows_current_month()
    {
        Carbon::setTestNow('2026-02-15');

        // 1. ログインする
        $this->actingAs($this->user);

        // 2. 勤怠一覧ページを開く
        $response = $this->get(route('attendance.list'));

        // 【期待挙動】現在の月が表示されていることを確認
        $response->assertSee('2026/02');
    }

    /**
     * 3. 「前月」を押下した時に表示月の前月の情報が表示される
     */
    public function test_attendance_list_navigates_to_previous_month_button()
    {
        Carbon::setTestNow('2026-02-15');

        // 1. 前月(1月)の勤怠情報を登録
        $attendance = AttendanceRecord::create([
            'user_id' => $this->user->id,
            'date' => '2026-01-20',
            'clock_in' => '2026-01-20 09:00:00',
            'clock_out' => '2026-01-20 18:30:00',
        ]);
        Rest::create([
            'attendance_record_id' => $attendance->id,
            'rest_in' => '2026-01-20 12:00:00',
            'rest_out' => '2026-01-20 13:30:00',
        ]);

        // 2. ログインする
        $this->actingAs($this->user);

        // 3. 勤怠一覧ページを開く
        $response = $this->get(route('attendance.list'));

        // 4. HTMLから「前月」ボタン（リンク）のURLを抽出して「押下」をシミュレート
        $prevMonthUrl = route('attendance.list', ['month' => '2026-01']);
        $response->assertSee($prevMonthUrl);
        $response = $this->get($prevMonthUrl);

        // 【期待挙動】前月の月表示と、登録した5項目を確認
        $response->assertSee('2026/01');
        $response->assertSee('01/20');
        $response->assertSee('09:00');
        $response->assertSee('18:30');
        $response->assertSee('1:30');
        $response->assertSee('8:00');
    }

    /**
     * 4. 「翌月」を押下した時に表示月の翌月の情報が表示される
     */
    public function test_attendance_list_navigates_to_next_month_button()
    {
        Carbon::setTestNow('2026-02-15');

        // 1. 翌月(3月)の勤怠情報を登録
        $attendance = AttendanceRecord::create([
            'user_id' => $this->user->id,
            'date' => '2026-03-10',
            'clock_in' => '2026-03-10 08:00:00',
            'clock_out' => '2026-03-10 20:00:00',
        ]);
        Rest::create([
            'attendance_record_id' => $attendance->id,
            'rest_in' => '2026-03-10 12:00:00',
            'rest_out' => '2026-03-10 14:00:00',
        ]);

        // 2. ログインする
        $this->actingAs($this->user);

        // 3. 勤怠一覧ページを開く
        $response = $this->get(route('attendance.list'));

        // 4. 「翌月」ボタン（リンク）のURLを抽出して「押下」
        $nextMonthUrl = route('attendance.list', ['month' => '2026-03']);
        $response->assertSee($nextMonthUrl);
        $response = $this->get($nextMonthUrl);

        // 【期待挙動】翌月の月表示と、登録した5項目を確認
        $response->assertSee('2026/03');
        $response->assertSee('03/10');
        $response->assertSee('08:00');
        $response->assertSee('20:00');
        $response->assertSee('2:00');
        $response->assertSee('10:00');
    }

    /**
     * 5. 「詳細」を押下すると、その日の勤怠詳細画面に遷移する
     */
    public function test_attendance_list_click_detail_button()
    {
        // 1. 特定の日のフル勤怠情報を登録
        $targetDate = '2026-02-16';
        $attendance = AttendanceRecord::create([
            'user_id' => $this->user->id,
            'date' => $targetDate,
            'clock_in' => $targetDate . ' 09:00:00',
            'clock_out' => $targetDate . ' 18:00:00',
        ]);
        Rest::create([
            'attendance_record_id' => $attendance->id,
            'rest_in' => $targetDate . ' 12:00:00',
            'rest_out' => $targetDate . ' 13:00:00',
        ]);

        // 2. ログインする
        $this->actingAs($this->user);

        // 3. 勤怠一覧ページを開く
        $response = $this->get(route('attendance.list', ['month' => '2026-02']));

        // 4. 一覧画面に 02/16 のデータが出ていることを確認
        $response->assertSee('02/16');

        // 5. 「詳細」リンクを取得して遷移（ボタンを押すアクション）
        $detailUrl = route('attendance.detail', ['id' => $attendance->id]);
        $response->assertSee($detailUrl);
        $response = $this->get($detailUrl);

        // 【期待挙動】詳細画面へ正しく遷移し、その日の日付が表示されている
        $response->assertStatus(200);
        $response->assertSee('勤怠詳細');

        // 詳細画面内で「2026」年や「2月16日」が表示されているかを確認
        $response->assertSee('2026');
        $response->assertSee('2');
        $response->assertSee('16');
    }
}
