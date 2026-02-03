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
        // 1. 【見本再現】2023年6月のデータ
        $this->seedFixedJune2023Data();

        // 2. 【機能検証】先月〜昨日までの直近データ
        $this->seedRecentData();
    }

    private function seedFixedJune2023Data(): void
    {
        // 一般画面用（伶奈）
        $reina_i = User::where('name', '西 伶奈')->first();
        $off_i = [4, 7, 17, 25];
        foreach (range(1, 30) as $day) {
            if (!in_array($day, $off_i)) $this->saveRecord($reina_i->id, "2023-06-" . sprintf('%02d', $day));
        }

        // 管理画面用（玲奈）
        $reina_m = User::where('name', '西 玲奈')->first();
        $off_m = [3, 4, 11, 18, 25];
        foreach (range(1, 30) as $day) {
            if (!in_array($day, $off_m)) $this->saveRecord($reina_m->id, "2023-06-" . sprintf('%02d', $day));
        }

        // 山田 花子（6/2のみ）
        $hanako = User::where('name', '山田 花子')->first();
        if ($hanako) $this->saveRecord($hanako->id, "2023-06-02");

        // その他スタッフ
        $others = User::whereNotIn('name', ['管理者', '西 伶奈', '西 玲奈', '山田 花子'])->get();
        foreach ($others as $staff) {
            foreach (range(1, 30) as $day) {
                if (!in_array($day, $off_m)) $this->saveRecord($staff->id, "2023-06-" . sprintf('%02d', $day));
            }
        }
    }

    private function seedRecentData(): void
    {
        $staffs = User::where('admin_status', 0)->get();
        $startDate = Carbon::today()->subMonth()->startOfMonth();
        $endDate = Carbon::today()->subDay();

        foreach ($staffs as $staff) {
            $current = $startDate->copy();
            while ($current <= $endDate) {
                if ($current->isWeekend()) {
                    $current->addDay();
                    continue;
                }
                $this->saveRecord($staff->id, $current->format('Y-m-d'));
                $current->addDay();
            }
        }
    }

    private function saveRecord($userId, $dateString)
    {
        AttendanceRecord::create([
            'user_id' => $userId,
            'date' => $dateString,
            'clock_in' => '09:00:00',
            'clock_out' => '18:00:00',
            // 合計時間はRestSeeder実行後にupdateされる運用、または初期値として1時間を引いた値をセット
            'total_rest_time' => '01:00:00',
            'total_time' => '08:00:00',
        ]);
    }
}
