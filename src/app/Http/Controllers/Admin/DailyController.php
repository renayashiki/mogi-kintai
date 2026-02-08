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

        // --- スタッフ一覧用の順序（西→山田）を維持した $users を取得 ---
        $users = User::where('name', '!=', '山田 花子')
            ->where('admin_status', '!=', 1)
            ->get(); // これで View 側の $user に対するエラーが消えます

        // --- 勤怠一覧用の順序（山田→西）を維持した $attendances を取得 ---
        $attendances = AttendanceRecord::with('user')
            ->whereHas('user', function ($query) {
                $query->where('name', '!=', '山田 花子')
                    ->where('admin_status', '!=', 1);
            })
            ->whereDate('date', $date->format('Y-m-d'))
            ->orderBy('clock_in', 'asc')
            ->get()
            ->unique('user_id');

        // 両方の変数を View に渡す
        return view('admin.daily', compact('users', 'attendances', 'date'));
    }
}
