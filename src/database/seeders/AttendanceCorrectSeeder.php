<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\AttendanceRecord;
use App\Models\AttendanceCorrect;

class AttendanceCorrectSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // 1. 西 伶奈の取得
        $west = User::where('name', '西 伶奈')->first();

        // 元の 6/1 のレコードを取得
        $originalRecord = AttendanceRecord::where('user_id', $west->id)
            ->where('date', '2023-06-01')
            ->first();

        // 西 伶奈：見本画像通り、同じ対象日(6/1)の申請が並ぶようにダミーを作成
        // ※ 1対1を守るため、親となる勤怠レコード自体をダミーとして複製します
        for ($i = 0; $i < 9; $i++) {
            // 勤怠レコードの複製（日付などは同じだが、IDが異なるレコードを作る）
            $dummyRecord = $originalRecord->replicate();
            $dummyRecord->save();

            // 複製したレコードに対して、1対1で申請を紐付け
            AttendanceCorrect::create([
                'user_id' => $west->id,
                'attendance_record_id' => $dummyRecord->id,
                'approval_status' => '承認待ち',
                'application_date' => '2023-06-02',
                'new_date' => '2023-06-01',
                'new_clock_in' => '09:00:00',
                'new_clock_out' => '18:00:00',
                'new_rest1_in' => '12:00:00',
                'new_rest1_out' => '13:00:00',
                'comment' => '遅延のため',
            ]);
        }

        // 2. 山田 太郎の申請
        $taro = User::where('name', '山田 太郎')->first();
        $taroRecord = AttendanceRecord::where('user_id', $taro->id)->where('date', '2023-06-01')->first();
        AttendanceCorrect::create([
            'user_id' => $taro->id,
            'attendance_record_id' => $taroRecord->id,
            'approval_status' => '承認待ち',
            'application_date' => '2023-08-02',
            'new_date' => '2023-06-01',
            'new_clock_in' => '09:00:00',
            'new_clock_out' => '18:00:00',
            'new_rest1_in' => '12:00:00',
            'new_rest1_out' => '13:00:00',
            'comment' => '遅延のため',
        ]);

        // 3. 山田 花子の申請
        $hanako = User::where('name', '山田 花子')->first();
        $hanakoRecord = AttendanceRecord::where('user_id', $hanako->id)->where('date', '2023-06-02')->first();
        AttendanceCorrect::create([
            'user_id' => $hanako->id,
            'attendance_record_id' => $hanakoRecord->id,
            'approval_status' => '承認待ち',
            'application_date' => '2023-07-02',
            'new_date' => '2023-06-02',
            'new_clock_in' => '09:00:00',
            'new_clock_out' => '18:00:00',
            'new_rest1_in' => '12:00:00',
            'new_rest1_out' => '13:00:00',
            'comment' => '遅延のため',
        ]);
    }
}
