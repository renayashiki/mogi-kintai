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

        // FN044: 遷移時に現在の月を表示、クエリパラメータがあればその月を表示
        $monthParam = $request->query('month');
        $currentMonth = $monthParam ? Carbon::parse($monthParam) : Carbon::now();

        // FN043: 選択したユーザーの当該月の勤怠情報を取得
        $attendances = AttendanceRecord::with('rests')->where('user_id', $id)
            ->whereYear('date', $currentMonth->year)
            ->whereMonth('date', $currentMonth->month)
            ->get()
            ->keyBy(function ($item) {
                return Carbon::parse($item->date)->format('Y-m-d');
            });
        return view('admin.staff-log', compact('user', 'currentMonth', 'attendances'));
    }

    // FN045: CSV出力機能
    // FN045: CSV出力機能
    public function exportCsv(Request $request, $id)
    {
        $user = User::findOrFail($id);
        $monthParam = $request->query('month');
        $currentMonth = $monthParam ? Carbon::parse($monthParam) : Carbon::now();

        $attendances = AttendanceRecord::where('user_id', $id)
            ->whereYear('date', $currentMonth->year)
            ->whereMonth('date', $currentMonth->month)
            ->orderBy('date', 'asc')
            ->get();

        $fileName = "attendance_{$user->name}_{$currentMonth->format('Ym')}.csv";

        // 直接 return することで $response が「未使用」と判定されるのを防ぎます
        return response()->streamDownload(function () use ($attendances, $currentMonth) {
            $handle = fopen('php://output', 'w');
            fwrite($handle, "\xEF\xBB\xBF"); // BOM追加

            fputcsv($handle, ['日付', '出勤', '退勤', '休憩', '合計']);

            $daysInMonth = $currentMonth->daysInMonth;
            for ($i = 1; $i <= $daysInMonth; $i++) {
                $date = $currentMonth->copy()->day($i);
                $attendance = $attendances->where('date', $date->format('Y-m-d'))->first();
                $dayName = ['日', '月', '火', '水', '木', '金', '土'][$date->dayOfWeek];

                fputcsv($handle, [
                    $date->format('m/d') . "($dayName)",
                    $attendance ? Carbon::parse($attendance->clock_in)->format('H:i') : '',
                    $attendance && $attendance->clock_out ? Carbon::parse($attendance->clock_out)->format('H:i') : '',
                    $attendance->total_rest_time ?? '',
                    $attendance->total_time ?? '',
                ]);
            }
            fclose($handle);
        }, $fileName, [
            'Content-Type' => 'text/csv',
        ]);
    }
}
