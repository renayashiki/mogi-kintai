<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\AttendanceRecord;
use App\Models\User;
use Carbon\Carbon;

class DailyController extends Controller
{
    public function index(Request $request)
    {
        $dateString = $request->get('date', now()->format('Y-m-d'));
        $date = Carbon::parse($dateString);

        // 管理職のみを除外（山田花子は表示対象に含める）
        $attendances = AttendanceRecord::with(['user', 'rests'])
            ->whereHas('user', function ($query) {
                // 山田花子の除外条件を削除し、管理者(1)以外のみを指定
                $query->where('admin_status', '!=', 1);
            })
            ->whereDate('date', $date->format('Y-m-d'))
            ->orderBy('clock_in', 'asc')
            ->get();

        return view('admin.daily', compact('attendances', 'dateString', 'date'));
    }
}
