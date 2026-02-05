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
        $date = \Carbon\Carbon::parse($dateString);

        // 全ユーザーを取得（山田花子を含む全スタッフ）
        $users = \App\Models\User::all();

        // 指定した日付の勤怠レコードを、ユーザーIDをキーにして取得
        $attendances = \App\Models\AttendanceRecord::whereDate('date', $date->format('Y-m-d'))
            ->get()
            ->keyBy('user_id'); // IDで引き当てやすくする

        return view('admin.daily', compact('users', 'attendances', 'date'));
    }
}
