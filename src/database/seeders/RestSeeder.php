<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\AttendanceRecord;
use App\Models\Rest;
use Carbon\Carbon;

class RestSeeder extends Seeder
{
    public function run()
    {
        $records = AttendanceRecord::with('user')->get();

        foreach ($records as $record) {
            // 2023年6月のデータは「見本通り」休憩1回(12-13時)に固定
            if (Carbon::parse($record->date)->format('Y-m') === '2023-06') {
                $this->createRest($record->id, '12:00:00', '13:00:00');
                continue;
            }

            // --- 2025年〜2026年のデータ（技術アピール：休憩を分割） ---
            // 30%の確率で休憩を2回に分ける（例：昼45分 ＋ 午後15分）
            if (rand(1, 100) <= 30) {
                // 1回目：12:00 - 12:45 (45分)
                $this->createRest($record->id, '12:00:00', '12:45:00');
                // 2回目：15:00 - 15:15 (15分)
                $this->createRest($record->id, '15:00:00', '15:15:00');

                // DB上の合計時間は「1時間」で維持される
                $record->update([
                    'total_rest_time' => '01:00:00',
                    'total_time' => '08:00:00',
                ]);
            } else {
                // 残り70%は通常の1時間休憩
                $this->createRest($record->id, '12:00:00', '13:00:00');
            }
        }
    }

    private function createRest($recordId, $in, $out)
    {
        Rest::create([
            'attendance_record_id' => $recordId,
            'rest_in' => $in,
            'rest_out' => $out,
        ]);
    }
}
