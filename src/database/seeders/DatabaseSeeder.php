<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Database\Seeders\RestSeeder;

class DatabaseSeeder extends Seeder
{
    public function run()
    {
        $this->call([
            UserSeeder::class,
            AttendanceRecordSeeder::class,
            RestSeeder::class,
            AttendanceCorrectSeeder::class,
        ]);
    }
}
