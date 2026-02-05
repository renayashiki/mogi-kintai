<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // 管理者
        User::create([
            'name' => '管理者',
            'email' => 'admin@coachtech.com',
            'password' => Hash::make('password123'),
            'admin_status' => 1,
        ]);

        // 1. 一般画面見本用：西 伶奈（メールも一般用）
        User::create([
            'name' => '西 伶奈',
            'email' => 'reina.n@coachtech.com',
            'password' => Hash::make('password123'),
            'admin_status' => 0,
        ]);

        // 2. 管理画面見本用：西 玲奈（漢字を"玲"に変更。メールは管理テスト用）
        User::create([
            'name' => '西 玲奈',
            'email' => 'reina.admin@coachtech.com',
            'password' => Hash::make('password123'),
            'admin_status' => 0,
        ]);

        $staffs = [
            ['name' => '山田 太郎', 'email' => 'taro.y@coachtech.com'],
            ['name' => '増田 一世', 'email' => 'issei.m@coachtech.com'],
            ['name' => '山本 敬吉', 'email' => 'keikichi.y@coachtech.com'],
            ['name' => '秋田 朋美', 'email' => 'tomomi.a@coachtech.com'],
            ['name' => '中西 教夫', 'email' => 'norio.n@coachtech.com'],
        ];

        // その他のスタッフ
        foreach ($staffs as $staff) {
            // 基本は「勤務外」
            $status = 'outside';

            // 特定のユーザーだけステータスを「予備」として固定
            if ($staff['name'] === '増田 一世') {
                $status = 'working';
            } elseif ($staff['name'] === '秋田 朋美') {
                $status = 'resting';
            } elseif ($staff['name'] === '中西 教夫') {
                $status = 'finished';
            }

            User::create([
                'name' => $staff['name'],
                'email' => $staff['email'],
                'password' => Hash::make('password123'),
                'admin_status' => 0,
                'attendance_status' => $status,
            ]);
        }

        User::create([
            'name' => '山田 花子',
            'email' => 'hanako.y@coachtech.com',
            'password' => Hash::make('password123'),
            'admin_status' => 0,
            'attendance_status' => 'outside',
        ]);
    }
}
