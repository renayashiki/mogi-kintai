<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        // 管理者
        User::create([
            'name' => '管理者',
            'email' => 'admin@coachtech.com',
            'password' => Hash::make('password123'),
            'admin_status' => 1,
            'email_verified_at' => now(),
        ]);

        // スタッフ一覧（画像3枚目の6名）
        $staffs = [
            ['name' => '西 伶奈', 'email' => 'reina.n@coachtech.com', 'status' => 'outside'],
            ['name' => '山田 太郎', 'email' => 'taro.y@coachtech.com', 'status' => 'outside'],
            ['name' => '増田 一世', 'email' => 'issei.m@coachtech.com', 'status' => 'outside'],
            ['name' => '山本 敬吉', 'email' => 'keikichi.y@coachtech.com', 'status' => 'outside'],
            ['name' => '秋田 朋美', 'email' => 'tomomi.a@coachtech.com', 'status' => 'outside'],
            ['name' => '中西 教夫', 'email' => 'norio.n@coachtech.com', 'status' => 'outside'],
        ];

        foreach ($staffs as $staff) {
            User::create([
                'name' => $staff['name'],
                'email' => $staff['email'],
                'password' => Hash::make('password123'),
                'admin_status' => 0,
                'attendance_status' => $staff['status'],
                'email_verified_at' => now(),
            ]);
        }
    }
}
