<?php

namespace Tests\Feature\Admin;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\User;
use App\Models\AttendanceRecord;
use App\Models\Rest;
use App\Models\AttendanceCorrect;
use Carbon\Carbon;

class AdminApprovalTest extends TestCase
{
    use RefreshDatabase;

    /**
     * ID:15 勤怠情報 修正機能(管理者)
     * 承認待ちの修正申請が全て表示されている
     */
    public function test_admin_can_view_all_pending_requests()
    {
        $admin = User::factory()->create(['admin_status' => 1]);
        $user1 = User::factory()->create(['name' => '西 怜奈']);
        $user2 = User::factory()->create(['name' => '山田 太郎']);

        // 申請日時を固定
        $appDate1 = Carbon::create(2026, 2, 1, 10, 0, 0);
        $appDate2 = Carbon::create(2026, 2, 2, 11, 30, 0);

        AttendanceCorrect::create([
            'user_id' => $user1->id,
            'attendance_record_id' => AttendanceRecord::factory()->create()->id,
            'approval_status' => '承認待ち',
            'new_date' => '2026-06-01',
            'comment' => '遅延のため',
            'application_date' => $appDate1,
            'new_clock_in' => '09:00:00',
            'new_clock_out' => '18:00:00',
        ]);

        AttendanceCorrect::create([
            'user_id' => $user2->id,
            'attendance_record_id' => AttendanceRecord::factory()->create()->id,
            'approval_status' => '承認待ち',
            'new_date' => '2026-06-01',
            'comment' => '打刻忘れ',
            'application_date' => $appDate2,
            'new_clock_in' => '09:00:00',
            'new_clock_out' => '18:00:00',
        ]);

        /** @var User $admin */
        $response = $this->actingAs($admin, 'admin')
            ->get(route('attendance.request.list', ['status' => 'pending']));

        $response->assertStatus(200);

        // 期待挙動：UI画像のカラム(状態・名前・対象日時・申請理由・申請日時)を網羅して確認
        // 1件目
        $response->assertSee('承認待ち');
        $response->assertSee('西 怜奈');
        $response->assertSee('2026/06/01');
        $response->assertSee('遅延のため');
        $response->assertSee($appDate1->format('Y/m/d')); // 申請日時

        // 2件目
        $response->assertSee('承認待ち');
        $response->assertSee('山田 太郎');
        $response->assertSee('2026/06/01');
        $response->assertSee('打刻忘れ');
        $response->assertSee($appDate2->format('Y/m/d'));
    }

    /**
     * 承認済みの修正申請が全て表示されている
     */
    public function test_admin_can_view_all_approved_requests()
    {
        $admin = User::factory()->create(['admin_status' => 1]);
        $user1 = User::factory()->create(['name' => '増田 一世']);
        $user2 = User::factory()->create(['name' => '佐藤 次郎']);

        $appDate1 = Carbon::create(2026, 2, 10, 9, 0, 0);
        $appDate2 = Carbon::create(2026, 2, 11, 15, 0, 0);

        // ユーザー1のデータ
        AttendanceCorrect::create([
            'user_id' => $user1->id,
            'attendance_record_id' => AttendanceRecord::factory()->create()->id,
            'approval_status' => '承認済み',
            'new_date' => '2026-06-01',
            'comment' => '修正完了分1',
            'application_date' => $appDate1,
            'new_clock_in' => '09:00:00',
            'new_clock_out' => '18:00:00',
        ]);

        // ユーザー2のデータ
        AttendanceCorrect::create([
            'user_id' => $user2->id,
            'attendance_record_id' => AttendanceRecord::factory()->create()->id,
            'approval_status' => '承認済み',
            'new_date' => '2026-06-02',
            'comment' => '修正完了分2',
            'application_date' => $appDate2,
            'new_clock_in' => '10:00:00',
            'new_clock_out' => '19:00:00',
        ]);

        /** @var User $admin */
        $response = $this->actingAs($admin, 'admin')
            ->get(route('attendance.request.list', ['status' => 'approved']));

        $response->assertStatus(200);

        // --- 期待挙動：承認済みタブで、全ユーザーのカラム情報が正確に表示されていること ---

        // 1人目：増田さんの詳細検証
        $response->assertSee('承認済み');
        $response->assertSee('増田 一世');
        $response->assertSee('2026/06/01');                // 対象日時
        $response->assertSee('修正完了分1');               // 申請理由
        $response->assertSee($appDate1->format('Y/m/d')); // 申請日時

        // 2人目：佐藤さんの詳細検証
        $response->assertSee('佐藤 次郎');
        $response->assertSee('2026/06/02');                // 対象日時
        $response->assertSee('修正完了分2');               // 申請理由
        $response->assertSee($appDate2->format('Y/m/d')); // 申請日時
    }

    /**
     * 修正申請の詳細内容が正しく表示されている
     */
    public function test_admin_can_view_request_detail_correctly()
    {
        $admin = User::factory()->create(['admin_status' => 1]);
        $user = User::factory()->create(['name' => '西 怜奈']);

        // 複雑な休憩データを含む申請を作成
        $request = AttendanceCorrect::create([
            'user_id' => $user->id,
            'attendance_record_id' => AttendanceRecord::factory()->create()->id,
            'approval_status' => '承認待ち',
            'new_date' => '2026-06-01',
            'new_clock_in' => '09:00:00',
            'new_clock_out' => '18:00:00',
            'new_rest1_in' => '12:00:00',
            'new_rest1_out' => '13:00:00',
            'comment' => '電車遅延のため',
            'application_date' => now(),
        ]);

        /** @var User $admin */
        $response = $this->actingAs($admin, 'admin')
            ->get(route('admin.request.approve', ['attendance_correct_request_id' => $request->id]));

        $response->assertStatus(200);
        // 期待挙動：UI画像に基づき、名前・日付・時間・休憩・備考が一致
        $response->assertSee('西　怜奈'); // 本番コードのstr_replace(全角)を考慮
        $response->assertSee('2026年');
        $response->assertSee('6月1日');
        $response->assertSee('09:00');
        $response->assertSee('18:00');
        $response->assertSee('12:00');
        $response->assertSee('13:00');
        $response->assertSee('電車遅延のため');
    }

    /**
     * 修正申請の承認処理が正しく行われる
     */
    public function test_admin_approval_reflects_on_all_relevant_screens()
    {
        // --- 準備：管理者と認証済みユーザー ---
        $admin = User::factory()->create(['admin_status' => 1]);
        $user = User::factory()->create([
            'admin_status' => 0,
            'email_verified_at' => now(), // ユーザー側画面アクセスに必要
        ]);

        // 元の勤怠データ（10:00〜15:00）
        $targetDate = Carbon::create(2026, 6, 1);
        $attendance = AttendanceRecord::create([
            'user_id' => $user->id,
            'date' => $targetDate->format('Y-m-d'),
            'clock_in' => '10:00:00',
            'clock_out' => '15:00:00',
        ]);

        // 修正申請データ：09:00〜18:00、休憩12:00〜13:00（勤務8時間）
        // ※Controllerのapproveロジックは new_rest1_in 等を元に本番テーブルを書き換える
        $request = AttendanceCorrect::create([
            'user_id' => $user->id,
            'attendance_record_id' => $attendance->id,
            'approval_status' => '承認待ち',
            'new_date' => $targetDate->format('Y-m-d'),
            'new_clock_in' => '09:00:00',
            'new_clock_out' => '18:00:00',
            'new_rest1_in' => '12:00:00',
            'new_rest1_out' => '13:00:00',
            'comment' => '全画面反映テスト',
            'application_date' => now(),
        ]);

        // --- 実行：承認ボタン押下（管理者として） ---
        /** @var User $admin */
        $response = $this->actingAs($admin, 'admin')
            ->post(route('admin.attendance.approve', ['attendance_correct_request_id' => $request->id]));

        // 承認後は「承認済み一覧」へリダイレクトされること
        $response->assertRedirect(route('attendance.request.list', ['status' => 'approved']));

        //  2. 期待挙動：リダイレクト先の一覧画面で、画像通りのカラムにデータが表示されていること
        // followRedirects() を使うか、再度 GET リクエストを送って検証します
        $this->get(route('attendance.request.list', ['status' => 'approved']))
            ->assertStatus(200)
            ->assertSee('承認済み')       // 状態
            ->assertSee($user->name)    // 名前
            ->assertSee('2026/06/01')   // 対象日時
            ->assertSee('全画面反映テスト') // 申請理由
            ->assertSee(now()->format('Y/m/d')); // 申請日時

        // --- 検証：管理者側画面の確認 ---

        // 1. 管理者：勤怠一覧（DailyController）
        // 期待：承認後の 09:00, 18:00, 1:00(休憩), 8:00(実働) が出ている
        $this->get(route('admin.attendance.list', ['date' => $targetDate->format('Y-m-d')]))
            ->assertSee($user->name)
            ->assertSee('09:00')
            ->assertSee('18:00')
            ->assertSee('1:00') // アクセサ経由
            ->assertSee('8:00'); // アクセサ経由

        // 2. 管理者：スタッフ別勤怠一覧（StaffLogController）
        $this->get(route('staff.log', ['id' => $user->id, 'month' => $targetDate->format('Y-m')]))
            ->assertSee($targetDate->format('m/d'))
            ->assertSee('09:00')
            ->assertSee('18:00')
            ->assertSee('1:00')
            ->assertSee('8:00');

        // 3. 管理者：勤怠詳細画面（EditController@show）
        // 期待：hasPendingRequestがfalseになり、本番レコード（更新後）が表示される
        $this->get(route('admin.attendance.detail', ['id' => $attendance->id]))
            ->assertSee('2026年')
            ->assertSee('6月1日')
            ->assertSee('09:00')
            ->assertSee('18:00')
            ->assertSee('12:00') // 休憩in
            ->assertSee('13:00') // 休憩out
            ->assertSee('全画面反映テスト');

        // --- 検証：ユーザー側画面の確認 ---

        // 4. ユーザー：勤怠一覧（MonthlyController）
        /** @var User $user */
        $this->actingAs($user, 'web')
            ->get(route('attendance.list', ['month' => $targetDate->format('Y-m')]))
            ->assertSee($targetDate->format('m/d'))
            ->assertSee('09:00')
            ->assertSee('18:00')
            ->assertSee('1:00')
            ->assertSee('8:00');

        // 5. ユーザー：勤怠詳細画面（WorkDetailController@show）
        // 期待：isApprovedがtrueになり、更新後の本番データが表示される
        $this->get(route('attendance.detail', ['id' => $attendance->id]))
            ->assertSee('09:00')
            ->assertSee('18:00')
            ->assertSee('12:00')
            ->assertSee('13:00')
            ->assertSee('全画面反映テスト');
    }
}
