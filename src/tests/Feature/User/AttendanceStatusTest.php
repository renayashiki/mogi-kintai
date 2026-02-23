<?php

namespace Tests\Feature\User;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\User;
use App\Models\AttendanceRecord;
use App\Models\Rest;
use Carbon\Carbon;

class AttendanceStatusTest extends TestCase
{
    use RefreshDatabase;

    /**
     * ID 5:ステータス確認機能
     * 勤務外の場合、勤怠ステータスが正しく表示される
     */
    public function test_status_is_outside_correctly()
    {
        // 1. ステータスが勤務外のユーザーを作成し、ログインする
        /** @var \App\Models\User $user */
        $user = User::factory()->create(['attendance_status' => 'outside']);
        $this->actingAs($user);

        // 2. 勤怠打刻画面を開く
        $response = $this->get(route('attendance.index'));

        // 3. 画面上に表示されているステータスが「勤務外」となる
        $response->assertStatus(200); // 正常に開けたことを確認
        $response->assertSee('<span class="status-text">勤務外</span>', false);
        $response->assertDontSee('出勤中');
        $response->assertDontSee('休憩中');
        $response->assertDontSee('退勤済');
    }

    /**
     * 出勤中の場合、勤怠ステータスが正しく表示される
     */
    public function test_status_is_working_correctly()
    {
        // 1. ステータスが出勤中のユーザーを作成し、ログインする
        /** @var \App\Models\User $user */
        $user = User::factory()->create(['attendance_status' => 'working']);

        AttendanceRecord::create([
            'user_id' => $user->id,
            'date' => Carbon::today(),
            'clock_in' => Carbon::now(),
        ]);

        $this->actingAs($user);

        // 2. 勤怠打刻画面を開く
        $response = $this->get(route('attendance.index'));

        // 3. 画面上に表示されているステータスが「出勤中」となる
        $response->assertStatus(200); // 正常に開けたことを確認
        $response->assertSee('<span class="status-text">出勤中</span>', false);
        $response->assertDontSee('勤務外');
        $response->assertDontSee('休憩中');
        $response->assertDontSee('退勤済');
    }

    /**
     * 休憩中の場合、勤怠ステータスが正しく表示される
     */
    public function test_status_is_resting_correctly()
    {
        // 1. ステータスが休憩中のユーザーを作成し、ログインする
        /** @var \App\Models\User $user */
        $user = User::factory()->create(['attendance_status' => 'resting']);

        $record = AttendanceRecord::create([
            'user_id' => $user->id,
            'date' => Carbon::today(),
            'clock_in' => Carbon::now(),
        ]);

        Rest::create([
            'attendance_record_id' => $record->id,
            'rest_in' => Carbon::now(),
        ]);

        $this->actingAs($user);

        // 2. 勤怠打刻画面を開く
        $response = $this->get(route('attendance.index'));

        // 3. 画面上に表示されているステータスが「休憩中」となる
        $response->assertStatus(200); // 正常に開けたことを確認
        $response->assertSee('<span class="status-text">休憩中</span>', false);
        $response->assertDontSee('勤務外');
        $response->assertDontSee('出勤中');
        $response->assertDontSee('退勤済');
    }

    /**
     * 退勤済の場合、勤怠ステータスが正しく表示される
     */
    public function test_status_is_finished_correctly()
    {
        // 1. ステータスが退勤済のユーザーを作成し、ログインする
        /** @var \App\Models\User $user */
        $user = User::factory()->create(['attendance_status' => 'finished']);

        AttendanceRecord::create([
            'user_id' => $user->id,
            'date' => Carbon::today(),
            'clock_in' => Carbon::now()->subHours(8),
            'clock_out' => Carbon::now(),
        ]);

        $this->actingAs($user);

        // 2. 勤怠打刻画面を開く
        $response = $this->get('/attendance?status=finished');

        // 3. 画面上に表示されているステータスが「退勤済」となる
        $response->assertStatus(200); // 正常に開けたことを確認
        $response->assertSee('<span class="status-text">退勤済</span>', false);
        $response->assertDontSee('勤務外');
        $response->assertDontSee('出勤中');
        $response->assertDontSee('休憩中');
    }
}
