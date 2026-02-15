<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class RestFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        return [
            'attendance_record_id' => \App\Models\AttendanceRecord::factory(),
            'rest_in' => '12:00:00',
            'rest_out' => '13:00:00',
        ];
    }
}
