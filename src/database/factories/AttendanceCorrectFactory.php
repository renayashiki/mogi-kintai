<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class AttendanceCorrectFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        return [
            'user_id' => \App\Models\User::factory(),
            'attendance_record_id' => \App\Models\AttendanceRecord::factory(),
            'approval_status' => '承認待ち',
            'application_date' => now()->format('Y-m-d'),
            'new_date' => now()->format('Y-m-d'),
            'new_clock_in' => '09:00',
            'new_clock_out' => '18:00',
            'comment' => '電車遅延のため',
        ];
    }
}
