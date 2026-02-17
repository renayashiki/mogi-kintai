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
        // 2026年2月16日 10:00:00 に時間を固定
        Carbon::setTestNow(Carbon::create(2026, 2, 16, 10, 0, 0));
        $this->user = User::factory()->create();
    }

    /**
     * ID:7 休憩機能
     * 休憩ボタンが正しく機能する
     */
    public function test_rest_in_button_functions_correctly()
    {

        // 1. レコード作成
        AttendanceRecord::create([
            'user_id' => $this->user->id,
            'date'    => '2026-02-16',
            'clock_in' => '09:00:00',
        ]);

        // 2. 【ここが重要！】ユーザーステータスを出勤中に更新
        $this->user->update(['attendance_status' => 'working']);

        $this->actingAs($this->user);

        // 2. 出勤中なので「休憩入」ボタンが見えるはず
        $response = $this->get(route('attendance.index'));
        $response->assertSee('<input type="hidden" name="type" value="rest_in">', false);
        $response->assertSee('<button type="submit" class="stamp-button btn-white">休憩入</button>', false);

        // 3. 休憩入アクションを実行
        $this->post(route('attendance.store'), ['type' => 'rest_in']);

        // 4. ステータスが「休憩中」に変わっていることを確認
        $response = $this->get(route('attendance.index'));
        $response->assertSee('<span class="status-text">休憩中</span>', false);
    }

    /**
     * 休憩は一日に何回でもできる
     */
    public function test_can_rest_multiple_times()
    {
        // 1. レコード作成
        AttendanceRecord::create([
            'user_id' => $this->user->id,
            'date'    => '2026-02-16',
            'clock_in' => '09:00:00',
        ]);

        // 2. 【ここが重要！】ユーザーステータスを出勤中に更新
        $this->user->update(['attendance_status' => 'working']);

        $this->actingAs($this->user);

        // 1回日の休憩を完了
        $this->post(route('attendance.store'), ['type' => 'rest_in']);
        $this->post(route('attendance.store'), ['type' => 'rest_out']);

        // 3. 「休憩入」ボタンが表示されることを確認する
        $response = $this->get(route('attendance.index'));
        $response->assertSee('<input type="hidden" name="type" value="rest_in">', false);
        $response->assertSee('<button type="submit" class="stamp-button btn-white">休憩入</button>', false);

        // 【期待挙動】ただの文字ではなく、type="submit" のボタンとして「休憩入」が存在する
        $response->assertSee('<button type="submit" class="stamp-button btn-white">休憩入</button>', false);
    }


    /**
     * 休憩戻ボタンが正しく機能する
     */
    public function test_rest_out_button_functions_correctly()
    {
        // 1. レコード作成
        AttendanceRecord::create([
            'user_id' => $this->user->id,
            'date'    => '2026-02-16',
            'clock_in' => '09:00:00',
        ]);

        // 2. 【ここが重要！】ユーザーステータスを出勤中に更新
        $this->user->update(['attendance_status' => 'working']);

        $this->actingAs($this->user);

        // 2. 休憩入の処理を行う
        $this->post(route('attendance.store'), ['type' => 'rest_in']);

        // 【期待挙動】休憩戻ボタンがHTMLとして正しく表示されている
        $response = $this->get(route('attendance.index'));
        $response->assertSee('<input type="hidden" name="type" value="rest_out">', false);
        $response->assertSee('<button type="submit" class="stamp-button btn-white">休憩戻</button>', false);

        // 3. 休憩戻の処理を行う
        $this->post(route('attendance.store'), ['type' => 'rest_out']);

        // 【期待挙動】処理後にステータスが「出勤中」に変更される
        $response = $this->get(route('attendance.index'));
        $response->assertSee('<span class="status-text">出勤中</span>', false);
    }


    /**
     * 休憩戻は一日に何回でもできる
     */
    public function test_can_rest_out_multiple_times()
    {
        // 1. レコード作成
        AttendanceRecord::create([
            'user_id' => $this->user->id,
            'date'    => '2026-02-16',
            'clock_in' => '09:00:00',
        ]);

        // 2. 【ここが重要！】ユーザーステータスを出勤中に更新
        $this->user->update(['attendance_status' => 'working']);
        $this->actingAs($this->user);

        // 1. 1回目の休憩完了 -> 2回目の休憩開始
        $this->post(route('attendance.store'), ['type' => 'rest_in']);
        $this->post(route('attendance.store'), ['type' => 'rest_out']);
        $this->post(route('attendance.store'), ['type' => 'rest_in']);

        // 2. 2回目の「休憩戻」ボタンが表示されているか
        $response = $this->get(route('attendance.index'));
        $response->assertSee('<input type="hidden" name="type" value="rest_out">', false);
        $response->assertSee('<button type="submit" class="stamp-button btn-white">休憩戻</button>', false);

        // 3. 2回目の休憩戻を実行
        $this->post(route('attendance.store'), ['type' => 'rest_out']);
        $response = $this->get(route('attendance.index'));
        $response->assertSee('出勤中');
    }

    /**
     * 休憩時刻が勤怠一覧画面で確認できる
     */
    public function test_rest_time_is_recorded_correctly_in_list()
    {

        $testDate = \Carbon\Carbon::create(2026, 2, 16);
        \Carbon\Carbon::setTestNow($testDate->copy()->setTime(10, 0, 0));

        // 1. レコード作成
        AttendanceRecord::create([
            'user_id' => $this->user->id,
            'date'    => '2026-02-16',
            'clock_in' => '09:00:00',
        ]);

        // 2. 【ここが重要！】ユーザーステータスを出勤中に更新
        $this->user->update(['attendance_status' => 'working']);

        $this->actingAs($this->user);

        // 1. 出勤・休憩・休憩戻・退勤を行う
        // 10:00 休憩入
        \Carbon\Carbon::setTestNow($testDate->copy()->setTime(10, 0, 0));
        $this->post(route('attendance.store'), ['type' => 'rest_in']);

        // 11:00 休憩戻 (休憩合計 1:00)
        \Carbon\Carbon::setTestNow($testDate->copy()->setTime(11, 0, 0));
        $this->post(route('attendance.store'), ['type' => 'rest_out']);

        // 18:00 退勤
        \Carbon\Carbon::setTestNow($testDate->copy()->setTime(18, 0, 0));
        $this->post(route('attendance.store'), ['type' => 'clock_out']);

        // 2. 勤怠一覧画面を表示する
        $response = $this->get(route('attendance.list'));
        $response->assertStatus(200);

        // 3. 勤怠一覧画面から休憩の日付を確認する
        // デザインに基づき「02/16(月)」の後に「1:00」が出現することを順序込みで確認
        $expectedDateDisplay = '02/16(月)';
        $expectedRestTime = '1:00';

        $response->assertSeeInOrder([
            $expectedDateDisplay,
            $expectedRestTime
        ]);
        \Carbon\Carbon::setTestNow();
    }
}
