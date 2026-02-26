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
            $this->seedPeriod($staff->id, Carbon::create(2023, 3, 1), Carbon::create(2023, 9, 30));
            $this->seedPeriod($staff->id, Carbon::now()->subMonths(3), Carbon::now());
        }
    }

    private function seedPeriod($userId, $start, $end)
    {
        $current = $start->copy();
        while ($current <= $end) {
            if ($current->isToday()) {
                $current->addDay();
                if ($current->isSunday() && $current->dayOfWeek === 0) {
                    $current->addDay();
                }
                continue;
            }
            if ($current->isWeekday()) {
                $this->saveRecord($userId, $current->format('Y-m-d'));
            } elseif ($current->isSaturday()) {
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
            if ($current->isSunday() && $current->dayOfWeek === 0) {
                $current->addDay();
            }
        }
    }

    private function saveRecord($userId, $dateString)
    {
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
