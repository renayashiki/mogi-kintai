<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User;
use Carbon\Carbon;

class DailyController extends Controller
{
    public function index(Request $request)
    {
        $dateString = $request->get('date', now()->format('Y-m-d'));
        $date = Carbon::parse($dateString);

        // 管理者以外の全スタッフを、その日の勤怠（および休憩データ）ごと取得
        $staffs = User::where('admin_status', '!=', 1)
            ->with(['attendanceRecords' => function ($query) use ($date) {
                $query->whereDate('date', $date->format('Y-m-d'))
                    ->with('rests');
            }])
            ->get();

        return view('admin.daily', compact('staffs', 'dateString', 'date'));
    }
}
