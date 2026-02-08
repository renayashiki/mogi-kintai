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

        // 山田花子と管理職を除外
        $attendances = AttendanceRecord::with(['user', 'rests'])
            ->whereHas('user', function ($query) {
                $query->where('name', '!=', '山田 花子')
                    ->where('admin_status', '!=', 1);
            })
            ->whereDate('date', $date->format('Y-m-d'))
            ->orderBy('clock_in', 'asc') // 山田太郎→西伶奈の順（秒数で制御）
            ->get();

        return view('admin.daily', compact('attendances', 'dateString'));
    }
}
