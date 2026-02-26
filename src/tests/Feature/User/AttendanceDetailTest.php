<?php

namespace Tests\Feature\User;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\User;
use App\Models\AttendanceRecord;
use App\Models\Rest;


class AttendanceDetailTest extends TestCase
{
    use RefreshDatabase;

    protected $user;
    protected $attendance;
    protected function setUp(): void
    {
        parent::setUp();
        \Carbon\Carbon::setTestNow(\Carbon\Carbon::create(2026, 2, 16, 10, 0, 0));
        $this->user = User::factory()->create(['name' => 'テスト太郎']);
        $this->attendance = AttendanceRecord::create([
            'user_id' => $this->user->id,
            'date' => '2026-02-16',
            'clock_in' => '2026-02-16 09:00:00',
            'clock_out' => '2026-02-16 18:00:00',
        ]);
        Rest::create([
            'attendance_record_id' => $this->attendance->id,
            'rest_in' => '2026-02-16 12:00:00',
            'rest_out' => '2026-02-16 13:00:00',
        ]);
    }

    protected function tearDown(): void
    {
        \Carbon\Carbon::setTestNow();
        parent::tearDown();
    }

    /**
     * ID:10 勤怠詳細情報 取得機能
     * 1. 勤怠詳細画面の「名前」がログインユーザーの氏名になっている
     */
    public function test_attendance_detail_shows_correct_user_name()
    {
        $this->actingAs($this->user);
        $response = $this->get(route('attendance.detail', ['id' => $this->attendance->id]));
        $response->assertSeeInOrder(['名前', 'テスト太郎']);
    }

    /**
     * 2. 勤怠詳細画面の「日付」が選択した日付になっている
     */
    public function test_attendance_detail_shows_correct_date()
    {
        $this->actingAs($this->user);
        $response = $this->get(route('attendance.detail', ['id' => $this->attendance->id]));
        $response->assertSee('2026年');
        $response->assertSee('2月16日');
    }

    /**
     * 3. 「出勤・退勤」にて記されている時間がログインユーザーの打刻と一致している
     */
    public function test_attendance_detail_shows_correct_work_times()
    {
        $this->actingAs($this->user);
        $response = $this->get(route('attendance.detail', ['id' => $this->attendance->id]));
        $response->assertSee('value="09:00"', false);
        $response->assertSee('value="18:00"', false);
    }

    /**
     * 4. 「休憩」にて記されている時間がログインユーザーの打刻と一致している
     */
    public function test_attendance_detail_shows_correct_rest_times()
    {
        $this->actingAs($this->user);
        $response = $this->get(route('attendance.detail', ['id' => $this->attendance->id]));
        $response->assertSee('12:00');
        $response->assertSee('13:00');
    }
}
