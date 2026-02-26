<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\AttendanceRecord;
use App\Models\Rest;
use Carbon\Carbon;

class RestSeeder extends Seeder
{
    public function run()
    {
        $records = AttendanceRecord::all();

        foreach ($records as $record) {
            if (Carbon::parse($record->date)->format('Y-m') === '2023-06') {
                $this->createRest($record->id, '12:00:00', '13:00:00');
                continue;
            }
            $dice = rand(1, 100);
            if ($dice <= 10) {
                $this->createRest($record->id, '12:00:00', '12:20:00');
                $this->createRest($record->id, '15:00:00', '15:20:00');
                $this->createRest($record->id, '17:00:00', '17:20:00');
                $record->update(['total_rest_time' => '01:00:00', 'total_time' => '08:00:00']);
            } elseif ($dice <= 40) {
                $this->createRest($record->id, '12:00:00', '12:45:00');
                $this->createRest($record->id, '15:00:00', '15:15:00');
                $record->update(['total_rest_time' => '01:00:00', 'total_time' => '08:00:00']);
            } else {
                $this->createRest($record->id, '12:00:00', '13:00:00');
            }
        }
    }

    private function createRest($recordId, $in, $out)
    {
        Rest::create([
            'attendance_record_id' => $recordId,
            'rest_in' => $in,
            'rest_out' => $out,
        ]);
    }
}
