<?php

namespace Tests\Feature\User;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\User;
use App\Models\AttendanceRecord;
use Carbon\Carbon;

class ClockOutTest extends TestCase
{
    use RefreshDatabase;

    protected $user;
    protected function setUp(): void
    {
        parent::setUp();
        Carbon::setTestNow(Carbon::create(2026, 2, 16, 10, 0, 0));
        $this->user = User::factory()->create();
    }

    /**
     * ID:8 退勤機能
     * 退勤ボタンが正しく機能する
     */
    public function test_clock_out_button_functions_correctly()
    {
        AttendanceRecord::create([
            'user_id' => $this->user->id,
            'date' => now()->toDateString(),
            'clock_in' => '09:00:00',
        ]);
        $this->user->update(['attendance_status' => 'working']);
        $this->actingAs($this->user);
        $response = $this->get(route('attendance.index'));
        $response->assertSee('<button type="submit" class="stamp-button button-primary">退勤</button>', false);
        $response = $this->followingRedirects()
            ->post(route('attendance.store'), ['type' => 'clock_out']);
        $this->get(route('attendance.index', ['status' => 'finished']))
            ->assertSee('<span class="status-text">退勤済</span>', false);
        $this->assertEquals('finished', $this->user->fresh()->attendance_status);
        $this->assertDatabaseHas('attendance_records', [
            'user_id' => $this->user->id,
            'date' => '2026-02-16',
            'clock_out' => '10:00:00',
        ]);
    }

    /**
     * 退勤時刻が勤怠一覧画面で確認できる
     */
    public function test_clock_out_time_is_recorded_correctly_in_list()
    {
        $this->user->update(['attendance_status' => 'outside']);
        $this->actingAs($this->user);
        $testDate = Carbon::create(2026, 2, 16);
        Carbon::setTestNow($testDate->copy()->setTime(9, 0, 0));
        $this->post(route('attendance.store'), ['type' => 'clock_in']);
        Carbon::setTestNow($testDate->copy()->setTime(18, 0, 0));
        $this->post(route('attendance.store'), ['type' => 'clock_out']);
        $response = $this->get(route('attendance.list'));
        $response->assertStatus(200);
        $expectedDateDisplay = '02/16(月)';
        $expectedClockOut = '18:00';
        $response->assertSeeInOrder([
            $expectedDateDisplay,
            $expectedClockOut
        ]);
        Carbon::setTestNow();
    }
}
