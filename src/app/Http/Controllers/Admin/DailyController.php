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

        // --- 修正：実際のカラム名 admin_status を使用 ---
        // 1. 名前が「山田 花子」ではない
        // 2. admin_status が 1（管理者）ではない
        // この条件で「一般スタッフのみ」を抽出します。
        $users = User::where('name', '!=', '山田 花子')
            ->where('admin_status', '!=', 1)
            ->get();

        // 指定した日付の勤怠レコードを取得
        $attendances = AttendanceRecord::whereDate('date', $date->format('Y-m-d'))
            ->get()
            ->keyBy('user_id');

        return view('admin.daily', compact('users', 'attendances', 'date'));
    }
}
