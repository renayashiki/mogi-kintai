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
     * 出勤ボタンが正しく機能する
     * 1. ステータスが勤務外のユーザーにログインする
     * 2. 画面に「出勤」ボタンが表示されていることを確認する
     * 3. 出勤の処理を行う
     */
    public function test_clock_in_button_functions_correctly()
    {
        // 1. ステータスが勤務外(outside)のユーザーを作成しログイン
        /** @var \App\Models\User $user */
        $user = User::factory()->create(['attendance_status' => 'outside']);
        $this->actingAs($user);

        // 2. 画面に「出勤」ボタンが表示されていることを確認
        $response = $this->get(route('attendance.index'));
        $response->assertStatus(200);
        $response->assertSee('出勤');

        // 3. 出勤の処理を行う
        $response = $this->post(route('attendance.store'), ['type' => 'clock_in']);

        // 完了後のリダイレクトとステータス変化を確認
        $response->assertRedirect(route('attendance.index'));
        $this->get(route('attendance.index'))->assertSee('出勤中');
    }

    /**
     * 出勤は一日一回のみできる
     * 1. ステータスが退勤済であるユーザーにログインする
     * 2. 勤務（出勤）ボタンが表示されないことを確認する
     */
    public function test_cannot_clock_in_twice_after_finishing()
    {
        // 1. ステータスが退勤済(finished)であるユーザーを作成
        /** @var \App\Models\User $user */
        $user = User::factory()->create(['attendance_status' => 'finished']);

        // すでに今日のレコードが存在している状態を作る
        AttendanceRecord::create([
            'user_id' => $user->id,
            'date' => Carbon::today()->format('Y-m-d'),
            'clock_in' => Carbon::now()->subHours(8),
            'clock_out' => Carbon::now(),
        ]);

        $this->actingAs($user);

        // 2. 勤務（出勤）ボタンが表示されないことを確認
        $response = $this->get(route('attendance.index'));
        $response->assertStatus(200);

        // ヘッダーの「勤怠一覧」に反応しないよう、ボタンタグを厳密にチェック
        $response->assertDontSee('type="submit" class="stamp-button btn-black">出勤', false);
    }

    /**
     * 出勤時刻が勤怠一覧画面で確認できる
     * 1. ステータスが勤務外のユーザーにログインする
     * 2. 出勤の処理を行う
     * 3. 勤怠一覧画面から出勤の日付を確認する
     */
    public function test_clock_in_time_is_recorded_correctly_in_list()
    {
        // 1. ステータスが勤務外のユーザーを作成しログイン
        // 時刻を固定 (秒まで精密に記録)
        $testTime = Carbon::create(2026, 2, 16, 9, 0, 15);
        Carbon::setTestNow($testTime);

        /** @var \App\Models\User $user */
        $user = User::factory()->create(['attendance_status' => 'outside']);
        $this->actingAs($user);

        // 2. 出勤の処理を行う
        $this->post(route('attendance.store'), ['type' => 'clock_in']);

        // 3. 勤怠一覧画面から出勤の日付を確認する
        // 「計算は精密に、表示はシンプルに」の原則に基づき、表示は 09:00 を期待
        $response = $this->get(route('attendance.list', ['month' => '2026-02']));
        $response->assertStatus(200);

        $response->assertSee('02/16'); // 日付
        $response->assertSee('09:00'); // 切り捨てられた表示時刻

        Carbon::setTestNow(); // モック解除
    }
}
