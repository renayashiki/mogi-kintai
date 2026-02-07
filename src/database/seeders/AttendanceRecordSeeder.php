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
        // 1. 【見本再現】2023年6月の固定データ
        $this->seedJune2023Data();

        // 2. 【機能検証】評価時の3ヶ月前から「今日」まで（全員ランダム休暇あり）
        $this->seedDynamicEvaluationData();
    }

    private function seedJune2023Data(): void
    {
        $staffs = User::where('admin_status', 0)->get();

        foreach ($staffs as $staff) {
            // 2023年6月については、西伶奈さんは後でコントローラ側で出し分けるため全日作成。
            // 他の人は4〜5日のランダム休日を作成。
            $offDays = [];
            if ($staff->name !== '西 伶奈') {
                $pool = array_diff(range(2, 30), [2]); // 6/1は全員出勤。花子の6/2は後述で担保。
                $offDays = (array) array_rand(array_flip($pool), 5);
            }

            foreach (range(1, 30) as $day) {
                $dateString = "2023-06-" . sprintf('%02d', $day);

                if ($day === 1) { // 6/1 全員出勤
                    $this->saveRecord($staff->id, $dateString);
                    continue;
                }

                if ($staff->name === '山田 花子' && $day === 2) { // 山田花子 6/2出勤
                    $this->saveRecord($staff->id, $dateString);
                    continue;
                }

                if (in_array($day, $offDays)) continue;

                $this->saveRecord($staff->id, $dateString);
            }
        }
    }

    private function seedDynamicEvaluationData(): void
    {
        $staffs = User::where('admin_status', 0)->get();
        $startDate = Carbon::now()->subMonths(3)->startOfMonth();
        $endDate = Carbon::now();

        foreach ($staffs as $staff) {
            $current = $startDate->copy();
            while ($current <= $endDate) {
                // 平日（月〜金）は必ずレコードを作成（毎日出勤）
                if ($current->isWeekday()) {
                    $this->saveRecord($staff->id, $current->format('Y-m-d'));
                }

                // 土日の処理：毎週「土曜日か日曜日のどちらか一方だけ」にレコードを作る
                // これにより、土日のうち片方が「出勤（レコードあり）」、片方が「空白（休み）」になります
                if ($current->isSaturday()) {
                    // 土曜日か日曜日のどちらか一方でレコードを作る（例：ランダムで50%の確率）
                    // 毎週必ず1日休み（空白）を作りたいので、土曜日に作るか日曜日に作るかをランダム決定
                    if (rand(0, 1) === 0) {
                        $this->saveRecord($staff->id, $current->format('Y-m-d')); // 土曜出勤、日曜休み
                    } else {
                        // 土曜を飛ばして、翌日の日曜日にレコードを作る
                        $this->saveRecord($staff->id, $current->copy()->addDay()->format('Y-m-d')); // 土曜休み、日曜出勤
                    }
                }

                // while文を回すため1日進める（日曜日は土曜の時点で処理済みなら飛ばす等の調整が必要ですが、
                // 記述をシンプルにするため、判定を厳密にします）
                $current->addDay();
            }
        }
    }

    private function saveRecord($userId, $dateString)
    {
        $user = User::find($userId);
        $second = "05"; // デフォルト

        // 山田さんを 01秒、西さんを 02秒 に設定
        // これにより、打刻時間順（clock_in）で並べた時に山田さんが先に来る
        if ($user->name === '山田 太郎') {
            $second = "01";
        } elseif ($user->name === '西 伶奈') {
            $second = "02";
        }

        AttendanceRecord::create([
            'user_id' => $userId,
            'date' => $dateString,
            'clock_in' => "09:00:{$second}",  // 山田: 09:00:01 / 西: 09:00:02
            'clock_out' => "18:00:{$second}",
            'total_rest_time' => '01:00:00',
            'total_time' => '08:00:00',
        ]);
    }
}
