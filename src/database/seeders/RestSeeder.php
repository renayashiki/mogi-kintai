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
        // 直近数日間の判定用（昨日、一昨日、3日前）
        $recentDates = [
            Carbon::yesterday()->format('Y-m-d'),
            Carbon::today()->subDays(2)->format('Y-m-d'),
            Carbon::today()->subDays(3)->format('Y-m-d'),
        ];

        foreach ($records as $record) {
            // --- 共通：1回目の休憩（全員・全日程） ---
            Rest::create([
                'attendance_record_id' => $record->id,
                'rest_in' => '12:00:00',
                'rest_out' => '13:00:00',
            ]);

            // --- 山本 敬吉さんの「直近の日付」のみ、複数回休憩をばらまく ---
            // ※ 2023-06-01 はここに含まれないため、休憩1回（1時間）のまま維持されます。
            if ($record->user->name === '山本 敬吉' && in_array($record->date, $recentDates)) {
                // 2回目の休憩を追加（15:00 - 15:15）
                Rest::create([
                    'attendance_record_id' => $record->id,
                    'rest_in' => '15:00:00',
                    'rest_out' => '15:15:00',
                ]);
                // 勤務レコードの合計時間を更新（休憩1:15、勤務7:45）
                $record->update([
                    'total_rest_time' => '01:15:00',
                    'total_time' => '07:45:00',
                ]);
            }
        }
    }
}
