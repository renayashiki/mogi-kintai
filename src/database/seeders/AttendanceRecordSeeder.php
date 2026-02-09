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
        // 1. 【共通ロジック】全スタッフ（管理者以外）に対してデータを生成
        $this->seedAllAttendanceData();
    }

    private function seedAllAttendanceData(): void
    {
        $staffs = User::where('admin_status', 0)->get();

        // 対象期間：2023年3月〜現在まで（見本時期＋直近評価用）
        $startDate = Carbon::create(2023, 3, 1);
        $endDate = Carbon::now();

        foreach ($staffs as $staff) {
            $current = $startDate->copy();

            while ($current <= $endDate) {
                // 平日（月〜金）は必ず作成
                if ($current->isWeekday()) {
                    $this->saveRecord($staff->id, $current->format('Y-m-d'));
                }
                // 土日の処理：毎週「土日どちらか一方だけ」出勤させる
                // これにより月4〜5日の休みが自然に発生する
                elseif ($current->isSaturday()) {
                    if (rand(0, 1) === 0) {
                        $this->saveRecord($staff->id, $current->format('Y-m-d')); // 土曜出勤
                    } else {
                        // 土曜を飛ばして日曜に出勤
                        $nextDay = $current->copy()->addDay();
                        if ($nextDay <= $endDate) {
                            $this->saveRecord($staff->id, $nextDay->format('Y-m-d'));
                        }
                    }
                }

                $current->addDay();
                // 日曜を処理済みとして飛ばす（重複防止）
                if ($current->isSunday()) {
                    $current->addDay();
                }
            }
        }
    }

    private function saveRecord($userId, $dateString)
    {
        // ユーザー名での分岐を削除。秒数は一律 "00" で固定
        // UIでの表示は「計算は精密に、表示はシンプルに」の原則通り
        // Carbonやアクセサで "09:00" に変換されます

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
