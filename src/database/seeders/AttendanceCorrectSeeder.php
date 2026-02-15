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
        // 1. 西 伶奈の申請
        $west = User::where('name', '西 伶奈')->first();
        $westPendingIds = []; // 承認済みと被らせないためのメモ

        if ($west) {
            $allJuneRecords = AttendanceRecord::where('user_id', $west->id)
                ->whereBetween('date', ['2023-06-01', '2023-06-30'])
                ->get();

            if ($allJuneRecords->isNotEmpty()) {
                $fixedRecord = $allJuneRecords->where('date', '2023-06-01')->first();
                $otherRecords = $allJuneRecords->where('date', '!=', '2023-06-01');
                $randomCount = min($otherRecords->count(), 8);
                $randomRecords = $randomCount > 0 ? $otherRecords->random($randomCount) : collect();

                $targetRecords = collect();
                if ($fixedRecord) $targetRecords->push($fixedRecord);
                $targetRecords = $targetRecords->merge($randomRecords);

                foreach ($targetRecords as $record) {
                    $this->createRequest($west->id, $record, '承認待ち');
                    $westPendingIds[] = $record->id;
                }
            }
        }

        // 2. 山田 太郎（6月分）
        $this->seedSpecificUserRequest('山田 太郎', '2023-06-01');

        // --- B. 【新規】承認済みデータを「被らないように」作成 ---

        $staffs = User::where('admin_status', 0)->get();
        foreach ($staffs as $staff) {
            // 既に「承認待ち」として登録されたIDを除外して取得
            $query = AttendanceRecord::where('user_id', $staff->id);
            if ($staff->name === '西 伶奈') $query->whereNotIn('id', $westPendingIds);
            if ($staff->name === '山田 太郎') $query->where('date', '!=', '2023-06-01');

            $availableRecords = $query->get();

            foreach ($availableRecords as $record) {
                // 利用可能な日のうち、20%の確率で「承認済み」データを作成
                if (rand(1, 100) <= 20) {
                    $this->createRequest($staff->id, $record, '承認済み');
                }
            }
        }
    }

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
            'comment' => '承認待ち'
        ]);
    }

    private function seedSpecificUserRequest($name, $date)
    {
        $user = User::where('name', $name)->first();
        if ($user) {
            $record = AttendanceRecord::where('user_id', $user->id)->where('date', $date)->first();
            if ($record) {
                $this->createRequest($user->id, $record, '承認待ち');
            }
        }
    }
}
