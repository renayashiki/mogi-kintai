<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\AttendanceRecord;
use App\Models\AttendanceCorrect;
use Carbon\Carbon;

class AttendanceCorrectSeeder extends Seeder
{
    public function run(): void
    {
        $west = User::where('name', '西 伶奈')->first();
        if ($west) {
            $fixedRecord = AttendanceRecord::where('user_id', $west->id)
                ->where('date', '2023-06-01')
                ->first();
            if ($fixedRecord) {
                $this->createRequest($west->id, $fixedRecord, '承認待ち');
            }
            $otherJuneRecords = AttendanceRecord::where('user_id', $west->id)
                ->whereBetween('date', ['2023-06-02', '2023-06-30'])
                ->inRandomOrder()
                ->take(8)
                ->get();
            foreach ($otherJuneRecords as $record) {
                $this->createRequest($west->id, $record, '承認待ち');
            }
        }
        $this->seedSpecificUserRequest('山田 太郎', '2023-06-01');
        $staffs = User::where('admin_status', 0)->get();
        foreach ($staffs as $staff) {
            $query = AttendanceRecord::where('user_id', $staff->id);
            if ($staff->name === '西 伶奈') {
                $query->whereNotBetween('date', ['2023-06-01', '2023-06-30']);
            }
            if ($staff->name === '山田 太郎') {
                $query->where('date', '!=', '2023-06-01');
            }
            $availableRecords = $query->inRandomOrder()->take(rand(1, 2))->get();
            foreach ($availableRecords as $record) {
                $this->createRequest($staff->id, $record, '承認済み');
            }
        }
    }

    private function createRequest($userId, $record, $status)
    {
        AttendanceCorrect::create([
            'user_id' => $userId,
            'attendance_record_id' => $record->id,
            'approval_status' => $status,
            'application_date' => Carbon::parse($record->date)->addDay()->format('Y-m-d'),
            'new_date' => $record->date,
            'new_clock_in' => '09:00:00',
            'new_clock_out' => '18:00:00',
            'new_rest1_in' => '12:00:00',
            'new_rest1_out' => '13:00:00',
            'comment' => '電車遅延のため'
        ]);
    }

    private function seedSpecificUserRequest($name, $date)
    {
        $user = User::where('name', $name)->first();
        if ($user) {
            $record = AttendanceRecord::where('user_id', $user->id)
                ->where('date', $date)
                ->first();
            if ($record) {
                $this->createRequest($user->id, $record, '承認待ち');
            }
        }
    }
}
