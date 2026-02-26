<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AttendanceCorrect;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class ApprovalController extends Controller
{
    public function index(Request $request)
    {
        $status = $request->query('status', 'pending');
        $dbStatus = ($status === 'approved') ? '承認済み' : '承認待ち';
        $requests = AttendanceCorrect::with(['user'])
            ->where('approval_status', $dbStatus)
            ->orderBy('new_date', 'asc')
            ->get();
        return view('admin.requests', compact('requests', 'status'));
    }

    public function show($attendance_correct_request_id)
    {
        $correctionRequest = AttendanceCorrect::with(['user', 'attendanceCorrectRests', 'attendanceRecord'])
            ->findOrFail($attendance_correct_request_id);
        return view('admin.approve', compact('correctionRequest'));
    }

    public function approve($attendance_correct_request_id)
    {
        $correctionRequest = AttendanceCorrect::with('attendanceCorrectRests')->findOrFail($attendance_correct_request_id);
        DB::transaction(function () use ($correctionRequest) {
            $attendance = $correctionRequest->attendanceRecord;
            $attendance->rests()->delete();
            $newRests = [];
            if ($correctionRequest->new_rest1_in) {
                $newRests[] = ['in' => $correctionRequest->new_rest1_in, 'out' => $correctionRequest->new_rest1_out];
            }
            if ($correctionRequest->new_rest2_in) {
                $newRests[] = ['in' => $correctionRequest->new_rest2_in, 'out' => $correctionRequest->new_rest2_out];
            }
            foreach ($correctionRequest->attendanceCorrectRests as $extra) {
                $newRests[] = ['in' => $extra->new_rest_in, 'out' => $extra->new_rest_out];
            }
            foreach ($newRests as $restData) {
                $attendance->rests()->create([
                    'rest_in'  => $restData['in'],
                    'rest_out' => $restData['out'],
                ]);
            }
            $attendance->load('rests');
            $attendance->clock_in = Carbon::parse($correctionRequest->new_clock_in)->second(0);
            $attendance->clock_out = $correctionRequest->new_clock_out ? Carbon::parse($correctionRequest->new_clock_out)->second(0) : null;
            $restSec = $attendance->getRestSeconds();
            $workSec = $attendance->getWorkSeconds();
            DB::table('attendance_records')
                ->where('id', $attendance->id)
                ->update([
                    'date'            => $correctionRequest->new_date,
                    'clock_in' => Carbon::parse($correctionRequest->new_clock_in)->second(0)->format('H:i:00'),
                    'clock_out' => $correctionRequest->new_clock_out ? Carbon::parse($correctionRequest->new_clock_out)->second(0)->format('H:i:00') : null,
                    'total_rest_time' => $attendance->formatSecondsForDb($restSec),
                    'total_time'      => $attendance->formatSecondsForDb($workSec),
                    'comment'         => $correctionRequest->comment,
                    'updated_at'      => now(),
                ]);
            $correctionRequest->update(['approval_status' => '承認済み']);
        });
        return redirect()->route('attendance.request.list', ['status' => 'approved']);
    }
}
