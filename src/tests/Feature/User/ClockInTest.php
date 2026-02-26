<?php

namespace Tests\Feature\User;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\User;
use App\Models\AttendanceRecord;
use Carbon\Carbon;

class ClockInTest extends TestCase
{
    use RefreshDatabase;

    /**
     * ID 6:出勤機能
     * 出勤ボタンが正しく機能する
     */
    public function test_clock_in_button_functions_correctly()
    {
        /** @var \App\Models\User $user */
        $user = User::factory()->create(['attendance_status' => 'outside']);
        $this->actingAs($user);
        $response = $this->get(route('attendance.index'));
        $response->assertStatus(200);
        $response->assertSee('出勤');
        $response->assertSee('<button type="submit" class="stamp-button button-primary">出勤</button>', false);
        $response = $this->post(route('attendance.store'), ['type' => 'clock_in']);
        $response->assertRedirect(route('attendance.index'));
        $this->get(route('attendance.index'))->assertSee('<span class="status-text">出勤中</span>', false);
        $this->assertEquals('working', $user->fresh()->attendance_status);
    }

    /**
     * 出勤は一日一回のみできる
     */
    public function test_cannot_clock_in_twice_after_finishing()
    {
        /** @var \App\Models\User $user */
        $user = User::factory()->create(['attendance_status' => 'finished']);
        AttendanceRecord::create([
            'user_id' => $user->id,
            'date' => Carbon::today()->format('Y-m-d'),
            'clock_in' => '09:00:00',
            'clock_out' => '18:00:00',
        ]);
        $this->actingAs($user);
        $response = $this->get(route('attendance.index'));
        $response->assertStatus(200);
        $response->assertDontSee('type="submit" class="stamp-button button-primary">出勤', false);
        $response->assertDontSee('出勤');
    }

    /**
     * 出勤時刻が勤怠一覧画面で確認できる
     */
    public function test_clock_in_time_is_recorded_correctly_in_list()
    {
        $testTime = Carbon::create(2026, 2, 16, 9, 0, 15);
        Carbon::setTestNow($testTime);
        /** @var \App\Models\User $user */
        $user = User::factory()->create(['attendance_status' => 'outside']);
        $this->actingAs($user);
        $this->post(route('attendance.store'), ['type' => 'clock_in']);
        $response = $this->get(route('attendance.list', ['month' => '2026-02']));
        $response->assertStatus(200);
        $response->assertSeeInOrder([
            '02/16',
            '09:00',
        ]);
        $this->assertDatabaseHas('attendance_records', [
            'user_id' => $user->id,
            'date'     => '2026-02-16',
            'clock_in' => '09:00:00',
        ]);
        Carbon::setTestNow();
    }
}
