<?php

namespace Tests\Feature\User;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\User;
use App\Models\AttendanceRecord;
use App\Models\AttendanceCorrect;
use Carbon\Carbon;

class AttendanceCorrectionTest extends TestCase
{
    use RefreshDatabase;

    /**
     * ID:11 勤怠詳細情報 修正機能
     * 出勤時間が退勤時間より後になっている場合、エラーメッセージが表示される
     */
    public function test_error_when_clock_in_is_after_clock_out()
    {
        // 準備
        $user = User::factory()->create();
        $attendance = AttendanceRecord::factory()->create([
            'user_id' => $user->id,
            'clock_in' => '09:00:00',
            'clock_out' => '18:00:00',
        ]);

        // 1. 勤怠情報が登録されたユーザーにログインをする
        /** @var User $user */
        $this->actingAs($user);

        // 2. 勤怠詳細ページを開く
        $response = $this->get(route('attendance.detail', ['id' => $attendance->id]));
        $response->assertStatus(200);

        // 3. 出勤時間を退勤時間より後に設定する / 4. 保存処理をする
        $response = $this->post(route('attendance.update', ['id' => $attendance->id]), [
            'clock_in' => '20:00',
            'clock_out' => '18:00',
            'comment' => '修正理由',
        ]);

        // 期待挙動の確認
        $response->assertSessionHasErrors(['clock_out']);
        $this->get(route('attendance.detail', ['id' => $attendance->id]))
            ->assertSee('出勤時間もしくは退勤時間が不適切な値です');
    }

    /**
     * 休憩開始時間が退勤時間より後になっている場合、エラーメッセージが表示される
     */
    public function test_error_when_rest_in_is_after_clock_out()
    {
        $user = User::factory()->create();
        $attendance = AttendanceRecord::factory()->create(['user_id' => $user->id]);

        // 1. ログイン 2. 詳細ページを開く
        /** @var User $user */
        $this->actingAs($user);
        $this->get(route('attendance.detail', ['id' => $attendance->id]));

        // 3. 休憩開始(19:00) > 退勤(18:00) 4. 保存処理
        $response = $this->post(route('attendance.update', ['id' => $attendance->id]), [
            'clock_in' => '09:00',
            'clock_out' => '18:00',
            'rests' => [['in' => '19:00', 'out' => '19:30']],
            'comment' => '修正理由',
        ]);

        $response->assertSessionHasErrors(['rests.0.in']);
        $this->get(route('attendance.detail', ['id' => $attendance->id]))
            ->assertSee('休憩時間が不適切な値です');
    }

    /**
     * 休憩終了時間が退勤時間より後になっている場合、エラーメッセージが表示される
     */
    public function test_error_when_rest_out_is_after_clock_out()
    {
        $user = User::factory()->create();
        $attendance = AttendanceRecord::factory()->create(['user_id' => $user->id]);

        /** @var User $user */
        $this->actingAs($user);
        $this->get(route('attendance.detail', ['id' => $attendance->id]));

        // 3. 休憩終了(19:00) > 退勤(18:00) 4. 保存
        $response = $this->post(route('attendance.update', ['id' => $attendance->id]), [
            'clock_in' => '09:00',
            'clock_out' => '18:00',
            'rests' => [['in' => '12:00', 'out' => '19:00']],
            'comment' => '修正理由',
        ]);

        $response->assertSessionHasErrors(['rests.0.out']);
        $this->get(route('attendance.detail', ['id' => $attendance->id]))
            ->assertSee('休憩時間もしくは退勤時間が不適切な値です');
    }

    /**
     * 備考欄が未入力の場合、エラーメッセージが表示される
     */
    public function test_error_when_comment_is_empty()
    {
        $user = User::factory()->create();
        $attendance = AttendanceRecord::factory()->create(['user_id' => $user->id]);

        /** @var User $user */
        $this->actingAs($user);
        $this->get(route('attendance.detail', ['id' => $attendance->id]));

        // 3. 備考欄を未入力のまま 4. 保存
        $response = $this->post(route('attendance.update', ['id' => $attendance->id]), [
            'clock_in' => '09:00',
            'clock_out' => '18:00',
            'comment' => '',
        ]);

        $response->assertSessionHasErrors(['comment']);
        $this->get(route('attendance.detail', ['id' => $attendance->id]))
            ->assertSee('備考を記入してください');
    }

    /**
     * 修正申請処理が実行される
     */
    public function test_correction_request_and_admin_view_full_match()
    {
        // 準備：ユーザーと管理者
        $user = User::factory()->create(['name' => '西怜奈']);
        $admin = User::factory()->create(['admin_status' => 1]);
        $date = '2023-06-01';
        $attendance = AttendanceRecord::factory()->create([
            'user_id' => $user->id,
            'date' => $date,
            'clock_in' => '09:00:00',
            'clock_out' => '18:00:00',
        ]);

        // 1. ユーザーでログイン / 2. 詳細を修正し保存
        /** @var User $user */
        $this->actingAs($user);
        $this->post(route('attendance.update', ['id' => $attendance->id]), [
            'clock_in' => '10:00',
            'clock_out' => '19:00',
            'rests' => [['in' => '13:00', 'out' => '14:00']],
            'comment' => '電車遅延のため',
        ]);

        // 3. 管理者でログイン
        /** @var User $admin */
        $this->actingAs($admin, 'admin');

        // --- 申請一覧画面の確認 (画像 image_605262.png に基づく) ---
        $response = $this->get(route('attendance.request.list', ['status' => 'pending']));
        $response->assertStatus(200);
        $today = Carbon::now()->format('Y/m/d');
        $response->assertSee('承認待ち');
        $response->assertSee('西怜奈');
        $response->assertSee('2023/06/01');
        $response->assertSee('電車遅延のため');
        $response->assertSee($today); // フォーマット済みの変数を使用

        // --- 承認画面（詳細）の確認 (画像 image_60529f.png に基づく) ---
        $request = AttendanceCorrect::where('user_id', $user->id)->first();
        $response = $this->get(route('admin.request.approve', ['attendance_correct_request_id' => $request->id]));
        $response->assertStatus(200);
        $response->assertSee('西怜奈');
        $response->assertSee('2023年');
        $response->assertSee('6月1日');
        $response->assertSee('10:00'); // 修正後の出勤
        $response->assertSee('19:00'); // 修正後の退勤
        $response->assertSee('13:00'); // 修正後の休憩開始
        $response->assertSee('14:00'); // 修正後の休憩終了
        $response->assertSee('電車遅延のため'); // 備考
    }

    /**
     * 「承認待ち」にログインユーザーが行った申請が全て表示されている
     */
    public function test_user_can_see_own_pending_requests_with_full_details()
    {
        $user = User::factory()->create(['name' => '西怜奈']);
        $date = '2023-06-01';
        $attendance = AttendanceRecord::factory()->create(['user_id' => $user->id, 'date' => $date]);

        // 1. ログイン / 2. 修正・保存
        /** @var User $user */
        $this->actingAs($user);
        $this->post(route('attendance.update', ['id' => $attendance->id]), [
            'clock_in' => '08:00',
            'clock_out' => '17:00',
            'rests' => [['in' => '12:00', 'out' => '13:00']],
            'comment' => '自分で行った申請',
        ]);

        // 3. 申請一覧画面を確認する (image_605262.png の構成と一致確認)
        $response = $this->get(route('attendance.request.list', ['status' => 'pending']));
        $response->assertStatus(200);
        $response->assertSee('承認待ち');
        $response->assertSee('西怜奈');
        $response->assertSee('2023/06/01');
        $response->assertSee('自分で行った申請');
        $response->assertSee(now()->format('Y/m/d')); // 申請日
    }

    /**
     * 「承認済み」に管理者が承認した修正申請が全て表示されている
     */
    public function test_approved_requests_display_full_details_after_approval()
    {
        $user = User::factory()->create(['name' => '西怜奈']);
        $admin = User::factory()->create(['admin_status' => 1]);
        $attendance = AttendanceRecord::factory()->create(['user_id' => $user->id, 'date' => '2023-06-01']);

        // 1. ログイン / 2. 修正・保存
        /** @var User $user */
        $this->actingAs($user);
        $this->post(route('attendance.update', ['id' => $attendance->id]), [
            'clock_in' => '10:00',
            'clock_out' => '19:00',
            'rests' => [['in' => '12:00', 'out' => '13:00']],
            'comment' => '画像確認用承認済みデータ',
        ]);

        // 管理者でログインして承認
        $request = AttendanceCorrect::where('comment', '画像確認用承認済みデータ')->first();

        /** @var User $admin */
        $this->actingAs($admin, 'admin');
        $this->post(route('admin.attendance.approve', ['attendance_correct_request_id' => $request->id]));

        $request->update(['approval_status' => '承認済み']);

        // 3. ユーザーで申請一覧を開く / 4. 承認済みタブで内容が完全一致するか
        $this->actingAs($user);
        $response = $this->get(route('attendance.request.list', ['status' => 'approved']));
        $response->assertStatus(200);
        $response->assertSee('承認済み');
        $response->assertSee('西怜奈');
        $response->assertSee('2023/06/01');
        $response->assertSee('画像確認用承認済み');
        // 詳細ボタンの遷移先が正しいかも確認
        $response->assertSee(route('admin.request.approve', ['attendance_correct_request_id' => $request->id]));
    }

    /**
     * 各申請の「詳細」を押下すると、勤怠詳細画面に遷移する
     */
    public function test_detail_button_redirects_with_proper_workflow()
    {
        // 準備
        /** @var User $user */
        $user = User::factory()->create(['name' => '西怜奈']);
        $date = '2023-06-01';
        $attendance = AttendanceRecord::factory()->create([
            'user_id' => $user->id,
            'date' => $date
        ]);

        // 1. ログイン / 2. 修正・保存
        $this->actingAs($user);
        $this->post(route('attendance.update', ['id' => $attendance->id]), [
            'clock_in' => '09:00',
            'clock_out' => '18:00',
            'rests' => [
                ['in' => '12:00', 'out' => '13:00'],
                ['in' => '15:00', 'out' => '15:15'] // 休憩2も想定
            ],
            'comment' => '詳細遷移テスト',
        ]);

        // 3. 申請一覧画面を開く
        $response = $this->get(route('attendance.request.list', ['status' => 'pending']));

        // 4. 「詳細」ボタン（リンク）の存在確認と押下
        $detailUrl = route('attendance.detail', ['id' => $attendance->id]);
        $response->assertSee($detailUrl);
        $response = $this->get($detailUrl);
        $response->assertStatus(200);

        // --- 遷移後の詳細画面（image_5e1005.png）の内容一致確認 ---
        $response->assertSee('勤怠詳細');
        $response->assertSee('西怜奈');       // 名前が一致するか
        $response->assertSee('2023年');      // 年が一致するか
        $response->assertSee('6月1日');       // 月日が一致するか

        // 修正申請した時間が表示されているか
        $response->assertSee('09:00');       // 出勤
        $response->assertSee('18:00');       // 退勤
        $response->assertSee('12:00');       // 休憩1開始
        $response->assertSee('13:00');       // 休憩1終了
        $response->assertSee('15:00');       // 休憩2開始
        $response->assertSee('15:15');       // 休憩2終了
        $response->assertSee('詳細遷移テスト'); // 備考
    }
}
