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
        $records = AttendanceRecord::all();

        foreach ($records as $record) {
            // 2023年6月のデータは「見本通り」休憩1回(12-13時)に固定
            if (Carbon::parse($record->date)->format('Y-m') === '2023-06') {
                $this->createRest($record->id, '12:00:00', '13:00:00');
                continue;
            }

            // --- 2023年6月以外は、技術アピールのため休憩回数をバラけさせる ---
            $dice = rand(1, 100);

            if ($dice <= 10) {
                // 【NEW】10%の確率で「休憩3回」：FN021, FN026-4 の検証用
                $this->createRest($record->id, '12:00:00', '12:20:00'); // 20分
                $this->createRest($record->id, '15:00:00', '15:20:00'); // 20分
                $this->createRest($record->id, '17:00:00', '17:20:00'); // 20分

                // 合計休憩時間は1時間(01:00:00)として更新
                $record->update(['total_rest_time' => '01:00:00', 'total_time' => '08:00:00']);
            } elseif ($dice <= 40) {
                // 30%の確率で「休憩2回」
                $this->createRest($record->id, '12:00:00', '12:45:00'); // 45分
                $this->createRest($record->id, '15:00:00', '15:15:00'); // 15分

                $record->update(['total_rest_time' => '01:00:00', 'total_time' => '08:00:00']);
            } else {
                // 残り60%は通常の1時間休憩
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
