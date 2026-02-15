<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\AttendanceRecord;
use App\Models\AttendanceCorrect;
use Carbon\Carbon;

class AttendanceCorrectSeeder extends Seeder
{
    public function run(): void
    {
        // 1. 【承認待ち】西 伶奈の申請（6/1を確実に含め、計9件作成）
        $west = User::where('name', '西 伶奈')->first();

        if ($west) {
            // 6月1日のレコードをピンポイントで取得
            $fixedRecord = AttendanceRecord::where('user_id', $west->id)
                ->where('date', '2023-06-01')
                ->first();

            if ($fixedRecord) {
                // ① まずは 6/1 を確実に「承認待ち」で作成
                $this->createRequest($west->id, $fixedRecord, '承認待ち');
            }

            // ② 6/1 以外の 6月のレコードをランダムに 8件 取得して作成
            $otherJuneRecords = AttendanceRecord::where('user_id', $west->id)
                ->whereBetween('date', ['2023-06-02', '2023-06-30']) // 6/2以降から選ぶ
                ->inRandomOrder()
                ->take(8)
                ->get();

            foreach ($otherJuneRecords as $record) {
                $this->createRequest($west->id, $record, '承認待ち');
            }
        }

        // 2. 【承認待ち】山田 太郎（6/1固定）
        $this->seedSpecificUserRequest('山田 太郎', '2023-06-01');

        // --- B. 【承認済み】全スタッフに1〜2個ずつ作成 ---

        $staffs = User::where('admin_status', 0)->get();
        foreach ($staffs as $staff) {
            $query = AttendanceRecord::where('user_id', $staff->id);

            // 西さんの6月分は「承認待ち」専用なので、承認済みには絶対入れない
            if ($staff->name === '西 伶奈') {
                $query->whereNotBetween('date', ['2023-06-01', '2023-06-30']);
            }

            // 山田さんの6/1も承認済みには入れない
            if ($staff->name === '山田 太郎') {
                $query->where('date', '!=', '2023-06-01');
            }

            // 残りのデータから1〜2個をランダムに「承認済み」として作成
            $availableRecords = $query->inRandomOrder()->take(rand(1, 2))->get();

            foreach ($availableRecords as $record) {
                $this->createRequest($staff->id, $record, '承認済み');
            }
        }
    }

    /**
     * 申請レコード作成の共通メソッド
     */
    private function createRequest($userId, $record, $status)
    {
        AttendanceCorrect::create([
            'user_id' => $userId,
            'attendance_record_id' => $record->id,
            'approval_status' => $status,
            'application_date' => Carbon::parse($record->date)->addDay()->format('Y-m-d'),
            'new_date' => $record->date,
            'new_clock_in' => '09:00:00',
            'new_clock_out' => '18:00:00',
            'new_rest1_in' => '12:00:00',
            'new_rest1_out' => '13:00:00',
            'comment' => '電車遅延のため'
        ]);
    }

    /**
     * 特定ユーザーの特定日の「承認待ち」を作成するヘルパー
     */
    private function seedSpecificUserRequest($name, $date)
    {
        $user = User::where('name', $name)->first();
        if ($user) {
            $record = AttendanceRecord::where('user_id', $user->id)
                ->where('date', $date)
                ->first();
            if ($record) {
                $this->createRequest($user->id, $record, '承認待ち');
            }
        }
    }
}
