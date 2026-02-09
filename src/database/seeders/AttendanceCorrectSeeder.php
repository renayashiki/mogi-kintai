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
        // 1. 西 伶奈の申請（6/1固定 ＋ 残り8件をランダム分散）
        $west = User::where('name', '西 伶奈')->first();

        if ($west) {
            // 6月の全レコードを取得
            $allJuneRecords = AttendanceRecord::where('user_id', $west->id)
                ->whereBetween('date', ['2023-06-01', '2023-06-30'])
                ->get();

            if ($allJuneRecords->isNotEmpty()) {
                // (A) まず 6/1 のレコードを確保
                $fixedRecord = $allJuneRecords->where('date', '2023-06-01')->first();

                // (B) 6/1 以外のレコードから最大8件をランダムに抽出
                $otherRecords = $allJuneRecords->where('date', '!=', '2023-06-01');

                // random()は件数不足だとエラーになるので、実際の件数に合わせて調整
                $randomCount = min($otherRecords->count(), 8);
                $randomRecords = $randomCount > 0 ? $otherRecords->random($randomCount) : collect();

                // (A)と(B)を合体
                $targetRecords = collect();
                if ($fixedRecord) {
                    $targetRecords->push($fixedRecord);
                }
                $targetRecords = $targetRecords->merge($randomRecords);

                // 申請データ作成
                foreach ($targetRecords as $record) {
                    AttendanceCorrect::create([
                        'user_id' => $west->id,
                        'attendance_record_id' => $record->id,
                        'approval_status' => '承認待ち',
                        'application_date' => Carbon::parse($record->date)->addDay()->format('Y-m-d'),
                        'new_date' => $record->date,
                        'new_clock_in' => '09:00:00',
                        'new_clock_out' => '18:00:00',
                        'new_rest1_in' => '12:00:00',
                        'new_rest1_out' => '13:00:00',
                        'comment' => '電車遅延のため',
                    ]);
                }
            }
        }

        // 2. 山田 太郎 (6/1) & 3. 山田 花子 (6/2)
        $this->seedSpecificUserRequest('山田 太郎', '2023-06-01');
        $this->seedSpecificUserRequest('山田 花子', '2023-06-02');
    }

    private function seedSpecificUserRequest($name, $date)
    {
        $user = User::where('name', $name)->first();
        if ($user) {
            $record = AttendanceRecord::where('user_id', $user->id)->where('date', $date)->first();
            if ($record) {
                AttendanceCorrect::create([
                    'user_id' => $user->id,
                    'attendance_record_id' => $record->id,
                    'approval_status' => '承認待ち',
                    'application_date' => Carbon::parse($date)->addDay()->format('Y-m-d'),
                    'new_date' => $date,
                    'new_clock_in' => '09:00:00',
                    'new_clock_out' => '18:00:00',
                    'new_rest1_in' => '12:00:00',
                    'new_rest1_out' => '13:00:00',
                    'comment' => '電車遅延のため',
                ]);
            }
        }
    }
}
