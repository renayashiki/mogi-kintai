<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\AttendanceRecord;
use Illuminate\Http\Request;
use Carbon\Carbon;
use Symfony\Component\HttpFoundation\StreamedResponse;

class StaffLogController extends Controller
{
    public function index(Request $request, $id)
    {
        $user = User::findOrFail($id);

        // FN044: 遷移時に現在の月を表示、クエリパラメータがあればその月を表示
        $monthParam = $request->query('month');
        $currentMonth = $monthParam ? Carbon::parse($monthParam) : Carbon::now();

        // FN043: 選択したユーザーの当該月の勤怠情報を取得
        $attendances = AttendanceRecord::where('user_id', $id)
            ->whereYear('date', $currentMonth->year)
            ->whereMonth('date', $currentMonth->month)
            ->get()
            ->keyBy(function ($item) {
                return Carbon::parse($item->date)->format('Y-m-d');
            });
        return view('admin.staff-log', compact('user', 'currentMonth', 'attendances'));
    }

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

        $response = new StreamedResponse(function () use ($attendances, $currentMonth) {
            $handle = fopen('php://output', 'w');

            // 文字化け防止（BOM追加）
            fwrite($handle, "\xEF\xBB\xBF");

            // ヘッダー（UIの構成と一致させる）
            fputcsv($handle, ['日付', '出勤', '退勤', '休憩', '合計']);

            $daysInMonth = $currentMonth->daysInMonth;
            for ($i = 1; $i <= $daysInMonth; $i++) {
                $date = $currentMonth->copy()->day($i);
                $attendance = $attendances->where('date', $date->format('Y-m-d'))->first();

                $dayName = ['日', '月', '火', '水', '木', '金', '土'][$date->dayOfWeek];

                // 勤怠情報がないフィールドは空白にする
                fputcsv($handle, [
                    $date->format('m/d') . "($dayName)",
                    $attendance ? Carbon::parse($attendance->clock_in)->format('H:i') : '',
                    $attendance && $attendance->clock_out ? Carbon::parse($attendance->clock_out)->format('H:i') : '',
                    $attendance && $attendance->total_rest_time ? Carbon::parse($attendance->total_rest_time)->format('H:i') : '',
                    $attendance && $attendance->total_time ? Carbon::parse($attendance->total_time)->format('H:i') : '',
                ]);
            }
            fclose($handle);
        });

        $fileName = "attendance_{$user->name}_{$currentMonth->format('Ym')}.csv";
        $response->headers->set('Content-Type', 'text/csv');
        $response->headers->set('Content-Disposition', "attachment; filename=\"$fileName\"");

        return $response;
    }
}
