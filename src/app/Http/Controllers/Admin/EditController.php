<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AttendanceRecord;
use App\Models\AttendanceCorrect;
use App\Http\Requests\AdminEditRequest;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class EditController extends Controller
{
    public function show($id)
    {
        $attendance = AttendanceRecord::with(['rests', 'user'])->findOrFail($id);
        $pendingRequest = AttendanceCorrect::where('attendance_record_id', $id)
            ->where('approval_status', '承認待ち')
            ->with('attendanceCorrectRests')
            ->first();
        $hasPendingRequest = (bool)$pendingRequest;
        if ($hasPendingRequest) {
            $attendance->clock_in = $pendingRequest->new_clock_in;
            $attendance->clock_out = $pendingRequest->new_clock_out;
            $attendance->comment = $pendingRequest->comment;
            $newRests = collect();
            $makeRest = function ($in, $out) {
                return new \App\Models\Rest([
                    'rest_in'  => \Carbon\Carbon::parse($in)->second(0),
                    'rest_out' => \Carbon\Carbon::parse($out)->second(0)
                ]);
            };
            if ($pendingRequest->new_rest1_in) {
                $newRests->push($makeRest($pendingRequest->new_rest1_in, $pendingRequest->new_rest1_out));
            }
            if ($pendingRequest->new_rest2_in) {
                $newRests->push($makeRest($pendingRequest->new_rest2_in, $pendingRequest->new_rest2_out));
            }
            foreach ($pendingRequest->attendanceCorrectRests as $extra) {
                $newRests->push($makeRest($extra->new_rest_in, $extra->new_rest_out));
            }
            $attendance->setRelation('rests', $newRests);
        }
        return view('admin.detail', compact('attendance', 'hasPendingRequest'));
    }

    public function update(AdminEditRequest $request, $id)
    {
        $attendance = AttendanceRecord::findOrFail($id);
        $targetDate = $attendance->date->format('Y-m-d');
        DB::transaction(function () use ($request, $attendance, $targetDate) {
            $attendance->rests()->delete();
            if ($request->rests) {
                foreach ($request->rests as $restData) {
                    if (!empty($restData['in']) && !empty($restData['out'])) {
                        $attendance->rests()->create([
                            'rest_in'  => Carbon::parse($targetDate . ' ' . $restData['in'])->second(0),
                            'rest_out' => Carbon::parse($targetDate . ' ' . $restData['out'])->second(0),
                        ]);
                    }
                }
            }
            $attendance->clock_in = Carbon::parse($targetDate . ' ' . $request->clock_in)->second(0);
            $attendance->clock_out = $request->clock_out ? \Carbon\Carbon::parse($targetDate . ' ' . $request->clock_out)->second(0) : null;
            $attendance->comment = $request->comment;
            $attendance->load('rests');
            $totalWorkSeconds = $attendance->getWorkSeconds();
            $totalRestSeconds = $attendance->getRestSeconds();
            $attendance->update([
                'clock_in'  => $attendance->clock_in->second(0),
                'clock_out' => optional($attendance->clock_out)->second(0),
                'comment'   => $attendance->comment,
                'total_rest_time' => $attendance->formatSecondsForDb($totalRestSeconds),
                'total_time'      => $attendance->formatSecondsForDb($totalWorkSeconds),
            ]);
        });
        return redirect()->route('admin.attendance.list', ['date' => $targetDate]);
    }
}
