<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class AttendanceCorrectRestFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        return [
            'attendance_correct_id' => \App\Models\AttendanceCorrect::factory(),
            'new_rest_in' => '12:00',
            'new_rest_out' => '13:00',
        ];
    }
}
