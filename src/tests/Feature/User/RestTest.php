<?php

namespace Tests\Feature\User;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\User;
use App\Models\AttendanceRecord;
use Carbon\Carbon;

class RestTest extends TestCase
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
     * ID:7 休憩機能
     * 休憩ボタンが正しく機能する
     */
    public function test_rest_in_button_functions_correctly()
    {
        AttendanceRecord::create([
            'user_id' => $this->user->id,
            'date'    => now()->toDateString(),
            'clock_in' => '09:00:00',
        ]);
        $this->user->update(['attendance_status' => 'working']);
        $this->actingAs($this->user);
        $response = $this->get(route('attendance.index'));
        $response->assertSee('<input type="hidden" name="type" value="rest_in">', false);
        $response->assertSee('<button type="submit" class="stamp-button button-secondary">休憩入</button>', false);
        $this->post(route('attendance.store'), ['type' => 'rest_in']);
        $response = $this->get(route('attendance.index'));
        $response->assertSee('<span class="status-text">休憩中</span>', false);
        $response->assertDontSee('休憩入');
        $response->assertSee('休憩戻');
    }

    /**
     * 休憩は一日に何回でもできる
     */
    public function test_can_rest_multiple_times()
    {
        AttendanceRecord::create([
            'user_id' => $this->user->id,
            'date'    => now()->toDateString(),
            'clock_in' => '09:00:00',
        ]);
        $this->user->update(['attendance_status' => 'working']);
        $this->actingAs($this->user);
        $this->post(route('attendance.store'), ['type' => 'rest_in']);
        $this->post(route('attendance.store'), ['type' => 'rest_out']);
        $response = $this->get(route('attendance.index'));
        $response->assertStatus(200);
        $response->assertSee('<input type="hidden" name="type" value="rest_in">', false);
        $response->assertSee('<button type="submit" class="stamp-button button-secondary">休憩入</button>', false);
        $this->assertEquals(1, \App\Models\Rest::count());
        $this->assertEquals('working', $this->user->fresh()->attendance_status);
    }


    /**
     * 休憩戻ボタンが正しく機能する
     */
    public function test_rest_out_button_functions_correctly()
    {
        AttendanceRecord::create([
            'user_id' => $this->user->id,
            'date'    => now()->toDateString(),
            'clock_in' => '09:00:00',
        ]);
        $this->user->update(['attendance_status' => 'working']);
        $this->actingAs($this->user);
        $this->post(route('attendance.store'), ['type' => 'rest_in']);
        $response = $this->get(route('attendance.index'));
        $response->assertSee('<input type="hidden" name="type" value="rest_out">', false);
        $response->assertSee('<button type="submit" class="stamp-button button-secondary">休憩戻</button>', false);
        $this->post(route('attendance.store'), ['type' => 'rest_out']);
        $response = $this->get(route('attendance.index'));
        $response->assertSee('<span class="status-text">出勤中</span>', false);
        $this->assertEquals('working', $this->user->fresh()->attendance_status);
    }


    /**
     * 休憩戻は一日に何回でもできる
     */
    public function test_can_rest_out_multiple_times()
    {
        AttendanceRecord::create([
            'user_id' => $this->user->id,
            'date'    => now()->toDateString(),
            'clock_in' => '09:00:00',
        ]);
        $this->user->update(['attendance_status' => 'working']);
        $this->actingAs($this->user);
        $this->post(route('attendance.store'), ['type' => 'rest_in']);
        $this->post(route('attendance.store'), ['type' => 'rest_out']);
        $this->post(route('attendance.store'), ['type' => 'rest_in']);
        $response = $this->get(route('attendance.index'));
        $response->assertSee('<input type="hidden" name="type" value="rest_out">', false);
        $response->assertSee('<button type="submit" class="stamp-button button-secondary">休憩戻</button>', false);
        $this->post(route('attendance.store'), ['type' => 'rest_out']);
        $response = $this->get(route('attendance.index'));
        $response->assertSee('出勤中');
        $this->assertEquals(2, \App\Models\Rest::whereNotNull('rest_out')->count());
    }

    /**
     * 休憩時刻が勤怠一覧画面で確認できる
     */
    public function test_rest_time_is_recorded_correctly_in_list()
    {
        $testDate = \Carbon\Carbon::create(2026, 2, 16);
        \Carbon\Carbon::setTestNow($testDate->copy()->setTime(10, 0, 0));
        AttendanceRecord::create([
            'user_id' => $this->user->id,
            'date'    => now()->toDateString(),
            'clock_in' => '09:00:00',
        ]);
        $this->user->update(['attendance_status' => 'working']);
        $this->actingAs($this->user);
        \Carbon\Carbon::setTestNow($testDate->copy()->setTime(10, 0, 0));
        $this->post(route('attendance.store'), ['type' => 'rest_in']);
        \Carbon\Carbon::setTestNow($testDate->copy()->setTime(11, 0, 0));
        $this->post(route('attendance.store'), ['type' => 'rest_out']);
        \Carbon\Carbon::setTestNow($testDate->copy()->setTime(18, 0, 0));
        $this->post(route('attendance.store'), ['type' => 'clock_out']);
        $response = $this->get(route('attendance.list'));
        $response->assertStatus(200);
        $expectedDateDisplay = '02/16(月)';
        $expectedRestTime = '1:00';
        $response->assertSeeInOrder([
            $expectedDateDisplay,
            $expectedRestTime
        ]);
        \Carbon\Carbon::setTestNow();
    }
}
