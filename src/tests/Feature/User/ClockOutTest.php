<?php

namespace Tests\Feature\User;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\User;
use App\Models\AttendanceRecord; // 直接データを作成するために追加
use Carbon\Carbon;

class ClockOutTest extends TestCase
{
    use RefreshDatabase;

    protected $user;

    protected function setUp(): void
    {
        parent::setUp();
        // 2026年2月16日(月) に固定
        Carbon::setTestNow(Carbon::create(2026, 2, 16, 10, 0, 0));
        $this->user = User::factory()->create();
    }

    /**
     * 退勤ボタンが正しく機能する
     */
    public function test_clock_out_button_functions_correctly()
    {
        // 1. 勤務中のデータを登録する（ステータスが勤務中のユーザー状態を作る）
        AttendanceRecord::create([
            'user_id' => $this->user->id,
            'date' => '2026-02-16',
            'clock_in' => '2026-02-16 09:00:00',
        ]);
        $this->user->update(['attendance_status' => 'working']);

        // 2. ログインする（手順：登録後にログインを遵守）
        $this->actingAs($this->user);

        // 3. 画面に「退勤」ボタンが表示されていることを確認する
        $response = $this->get(route('attendance.index'));

        // 【期待挙動】画面上に「退勤」ボタンが表示される
        $response->assertSee('<button type="submit" class="stamp-button btn-black">退勤</button>', false);

        // 4. 退勤の処理を行う
        $response = $this->followingRedirects()
            ->post(route('attendance.store'), ['type' => 'clock_out']);

        // 【期待挙動】処理後に画面上に表示されるステータスが「退勤済」になる
        $response->assertSee('退勤済');
    }

    /**
     * 退勤時刻が勤怠一覧画面で確認できる
     */
    public function test_clock_out_time_is_recorded_correctly_in_list()
    {
        // 1. ステータスが勤務外のユーザーにログインする
        $this->actingAs($this->user);
        $testDate = Carbon::create(2026, 2, 16);

        // 2. 出勤と退勤の処理を行う
        // 09:00 出勤
        Carbon::setTestNow($testDate->copy()->setTime(9, 0, 0));
        $this->post(route('attendance.store'), ['type' => 'clock_in']);

        // 18:00 退勤
        Carbon::setTestNow($testDate->copy()->setTime(18, 0, 0));
        $this->post(route('attendance.store'), ['type' => 'clock_out']);

        // 3. 勤怠一覧画面から退勤の日付を確認する
        $response = $this->get(route('attendance.list'));
        $response->assertStatus(200);

        // 【期待挙動】勤怠一覧画面に退勤時刻が正確に記録されている
        // Figmaデザイン「02/16(月)」の後に「18:00」が表示されることを順序指定で確認
        $expectedDateDisplay = '02/16(月)';
        $expectedClockOut = '18:00';

        $response->assertSeeInOrder([
            $expectedDateDisplay,
            $expectedClockOut
        ]);
    }
}
