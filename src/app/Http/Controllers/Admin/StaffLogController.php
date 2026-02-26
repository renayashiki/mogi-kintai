<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\AttendanceRecord;
use Illuminate\Http\Request;
use Carbon\Carbon;


class StaffLogController extends Controller
{
    public function index(Request $request, $id)
    {
        $user = User::findOrFail($id);
        $monthParam = $request->query('month');
        $currentMonth = $monthParam ? Carbon::parse($monthParam) : Carbon::now();
        $attendances = AttendanceRecord::with('rests')->where('user_id', $id)
            ->whereYear('date', $currentMonth->year)
            ->whereMonth('date', $currentMonth->month)
            ->get()
            ->keyBy(function ($item) {
                return Carbon::parse($item->date)->format('Y-m-d');
            });
        return view('admin.staff-log', compact('user', 'currentMonth', 'attendances'));
    }

    public function exportCsv(Request $request, $id)
    {
        $user = User::findOrFail($id);
        $monthParam = $request->query('month');
        $currentMonth = $monthParam ? Carbon::parse($monthParam) : Carbon::now();
        $attendances = AttendanceRecord::with('rests')
            ->where('user_id', $id)
            ->whereYear('date', $currentMonth->year)
            ->whereMonth('date', $currentMonth->month)
            ->get()
            ->keyBy(function ($item) {
                return Carbon::parse($item->date)->format('Y-m-d');
            });
        $fileName = "attendance_{$user->name}_{$currentMonth->format('Ym')}.csv";
        return response()->streamDownload(function () use ($attendances, $currentMonth) {
            $handle = fopen('php://output', 'w');
            fwrite($handle, "\xEF\xBB\xBF");
            fputcsv($handle, ['日付', '出勤', '退勤', '休憩', '合計']);
            $daysInMonth = $currentMonth->daysInMonth;
            for ($i = 1; $i <= $daysInMonth; $i++) {
                $date = $currentMonth->copy()->day($i);
                $dateStr = $date->format('Y-m-d');
                $attendance = $attendances->get($dateStr);
                $dayName = ['日', '月', '火', '水', '木', '金', '土'][$date->dayOfWeek];
                fputcsv($handle, [
                    $date->format('m/d') . "($dayName)",
                    $attendance ? Carbon::parse($attendance->clock_in)->format('H:i') : '',
                    $attendance && $attendance->clock_out ? Carbon::parse($attendance->clock_out)->format('H:i') : '',
                    $attendance ? $attendance->total_rest_time : '',
                    $attendance ? $attendance->total_time : '',
                ]);
            }
            fclose($handle);
        }, $fileName, [
            'Content-Type' => 'text/csv',
        ]);
    }
}
