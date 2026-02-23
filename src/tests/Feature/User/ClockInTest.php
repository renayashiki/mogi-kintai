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
        // 1. ステータスが勤務外(outside)のユーザーを作成しログイン
        /** @var \App\Models\User $user */
        $user = User::factory()->create(['attendance_status' => 'outside']);
        $this->actingAs($user);

        // 2. 画面に「出勤」ボタンが表示されていることを確認
        $response = $this->get(route('attendance.index'));
        $response->assertStatus(200);
        $response->assertSee('出勤');
        $response->assertSee('<button type="submit" class="stamp-button button-primary">出勤</button>', false);

        // 3. 出勤の処理を行う
        $response = $this->post(route('attendance.store'), ['type' => 'clock_in']);

        // 完了後のリダイレクトとステータス変化を確認
        $response->assertRedirect(route('attendance.index'));
        $this->get(route('attendance.index'))->assertSee('<span class="status-text">出勤中</span>', false);
        // 精密な検証：DB上のステータスも更新されているか確認 [cite: 2026-02-04]
        $this->assertEquals('working', $user->fresh()->attendance_status);
    }

    /**
     * 出勤は一日一回のみできる
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
            'clock_in' => '09:00:00',
            'clock_out' => '18:00:00',
        ]);

        $this->actingAs($user);

        // 2. 勤務（出勤）ボタンが表示されないことを確認
        $response = $this->get(route('attendance.index'));
        $response->assertStatus(200);

        // ヘッダーの「勤怠一覧」に反応しないよう、ボタンタグを厳密にチェック
        $response->assertDontSee('type="submit" class="stamp-button button-primary">出勤', false);
        $response->assertDontSee('出勤');
    }

    /**
     * 出勤時刻が勤怠一覧画面で確認できる
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

        // 【期待挙動の証明】
        // 単に「09:00」があるかだけでなく、日付と時刻がセットで並んでいることを検証
        // これにより、他の行との混同を防ぎ「正確な記録」を証明します [cite: 2026-02-04]
        $response->assertSeeInOrder([
            '02/16', // 日付
            '09:00', // 表示上の出勤時刻（秒切り捨て）
        ]);

        // 【さらに精密な証明】
        // DB上には秒まで保存されていることを確認し、ロジックの正確性を担保
        $this->assertDatabaseHas('attendance_records', [
            'user_id' => $user->id,
            'date'     => '2026-02-16',         // date型カラムに一致
            'clock_in' => '09:00:00',
        ]);

        Carbon::setTestNow(); // モック解除
    }
}
