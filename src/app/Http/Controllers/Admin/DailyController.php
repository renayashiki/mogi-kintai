<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\AttendanceRecord;
use Carbon\Carbon;

class DailyController extends Controller
{
    public function index(Request $request)
    {
        $dateString = $request->get('date', now()->format('Y-m-d'));
        $date = Carbon::parse($dateString);

        // 管理者(admin_status=1)以外を表示対象とする
        $attendances = AttendanceRecord::with(['user', 'rests']) // restsを必ずロード
            ->whereHas('user', function ($query) {
                $query->where('admin_status', '!=', 1);
            })
            ->whereDate('date', $date->format('Y-m-d'))
            ->orderBy('clock_in', 'asc')
            ->get();

        return view('admin.daily', compact('attendances', 'dateString', 'date'));
    }
}
