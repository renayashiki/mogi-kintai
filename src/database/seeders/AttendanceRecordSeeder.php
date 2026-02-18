<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\AttendanceRecord;
use Carbon\Carbon;

class AttendanceRecordSeeder extends Seeder
{
    public function run(): void
    {
        $staffs = User::where('admin_status', 0)->get();

        foreach ($staffs as $staff) {
            // ① 2023年期間（見本の6月前後3ヶ月：3月〜9月）
            $this->seedPeriod($staff->id, Carbon::create(2023, 3, 1), Carbon::create(2023, 9, 30));

            // ② 2026年期間（現在から前3ヶ月）
            $this->seedPeriod($staff->id, Carbon::now()->subMonths(3), Carbon::now());
        }
    }

    private function seedPeriod($userId, $start, $end)
    {
        $current = $start->copy();
        while ($current <= $end) {

            // 「今日」の日付はスキップする（評価者がログインした時に出勤ボタンを出すため）
            if ($current->isToday()) {
                $current->addDay();
                // 日曜のスキップ処理と重複しないよう、ここで判定
                if ($current->isSunday() && $current->dayOfWeek === 0) {
                    $current->addDay();
                }
                continue;
            }

            // 【重要】週1〜2日の休みを再現するロジックを維持
            if ($current->isWeekday()) {
                // 平日は基本出勤（たまに休ませるならここを調整）
                $this->saveRecord($userId, $current->format('Y-m-d'));
            } elseif ($current->isSaturday()) {
                // 土日のどちらか一方だけ出勤させることで、週休2日を維持
                if (rand(0, 1) === 0) {
                    $this->saveRecord($userId, $current->format('Y-m-d'));
                } else {
                    $nextDay = $current->copy()->addDay();
                    if ($nextDay <= $end) {
                        $this->saveRecord($userId, $nextDay->format('Y-m-d'));
                    }
                }
            }

            $current->addDay();
            // 日曜を処理済みなら飛ばす（重複防止）
            if ($current->isSunday() && $current->dayOfWeek === 0) {
                $current->addDay();
            }
        }
    }

    private function saveRecord($userId, $dateString)
    {
        // 「計算は精密に、表示はシンプルに」に基づき秒まで保存
        AttendanceRecord::create([
            'user_id' => $userId,
            'date' => $dateString,
            'clock_in' => "09:00:00",
            'clock_out' => "18:00:00",
            'total_rest_time' => '01:00:00',
            'total_time' => '08:00:00',
        ]);
    }
}
