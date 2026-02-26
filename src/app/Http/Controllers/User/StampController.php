<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\AttendanceRecord;
use App\Models\Rest;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;

class StampController extends Controller
{
    public function index(Request $request)
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();
        $today = Carbon::today();
        $todayRecord = AttendanceRecord::where('user_id', $user->id)
            ->whereDate('date', $today)
            ->first();
        $hasClockIn = $todayRecord ? true : false;
        $hasClockOut = ($todayRecord && $todayRecord->clock_out) ? true : false;
        if ($request->query('status') === 'finished') {
            $attendanceStatus = 'finished';
        } elseif ($hasClockOut) {
            $attendanceStatus = 'outside';
        } elseif ($hasClockIn) {
            $attendanceStatus = $user->attendance_status;
        } else {
            $attendanceStatus = 'outside';
        }
        return view('user.stamp', compact('attendanceStatus', 'hasClockIn'));
    }

    public function store(Request $request)
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();
        $type = $request->input('type');
        $now = Carbon::now();
        $today = Carbon::today();
        switch ($type) {
            case 'clock_in':
                $exists = AttendanceRecord::where('user_id', $user->id)
                    ->whereDate('date', $today)
                    ->exists();
                if ($exists) {
                    return redirect()->route('attendance.index');
                }
                AttendanceRecord::create([
                    'user_id' => $user->id,
                    'date' => $today,
                    'clock_in' => $now->format('Y-m-d H:i:00'),
                ]);
                $user->update(['attendance_status' => 'working']);
                return redirect()->route('attendance.index');
            case 'rest_in':
                $record = AttendanceRecord::where('user_id', $user->id)->whereNull('clock_out')->latest()->first();
                Rest::create([
                    'attendance_record_id' => $record->id,
                    'rest_in' => $now->format('Y-m-d H:i:00'),
                ]);
                $user->update(['attendance_status' => 'resting']);
                return redirect()->route('attendance.index');
            case 'rest_out':
                $record = AttendanceRecord::where('user_id', $user->id)->whereNull('clock_out')->latest()->first();
                $rest = Rest::where('attendance_record_id', $record->id)->whereNull('rest_out')->first();
                if ($rest) {
                    $rest->update(['rest_out' => $now->format('Y-m-d H:i:00')]);
                }
                $user->update(['attendance_status' => 'working']);
                return redirect()->route('attendance.index');
            case 'clock_out':
                $alreadyClockedOut = AttendanceRecord::where('user_id', $user->id)
                    ->whereDate('date', $today)
                    ->whereNotNull('clock_out')
                    ->exists();
                if ($alreadyClockedOut) {
                    return redirect()->route('attendance.index');
                }
                $record = AttendanceRecord::where('user_id', $user->id)
                    ->whereNull('clock_out')
                    ->latest()
                    ->first();
                if (!$record) {
                    $record = AttendanceRecord::where('user_id', $user->id)->whereDate('date', $today)->first();
                }
                $nowFixed = $now->copy()->second(0);
                $record->load('rests');
                $record->clock_out = $nowFixed;
                $restSec = $record->getRestSeconds();
                $workSec = $record->getWorkSeconds();
                $totalRestDb = $record->formatSecondsForDb($restSec);
                $totalWorkDb = $record->formatSecondsForDb($workSec);
                $record->update([
                    'clock_out' => $nowFixed->format('Y-m-d H:i:00'),
                    'total_rest_time' => $totalRestDb,
                    'total_time' => $totalWorkDb,
                ]);
                $user->update(['attendance_status' => 'finished']);
                return redirect('/attendance?status=finished');
        }
    }
}
