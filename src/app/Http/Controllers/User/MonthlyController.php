<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\AttendanceRecord;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;

class MonthlyController extends Controller
{
    public function index(Request $request)
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();

        // クエリパラメータから月を取得、なければ今月
        $monthStr = $request->query('month', Carbon::now()->format('Y-m'));
        $currentMonth = Carbon::parse($monthStr)->startOfMonth();

        // 1ヶ月分のデータを取得（計算は精密に：1日から末日までを抽出）
        $attendances = AttendanceRecord::where('user_id', $user->id)
            ->whereYear('date', $currentMonth->year)
            ->whereMonth('date', $currentMonth->month)
            ->orderBy('date', 'asc')
            ->get()
            ->keyBy(function ($item) {
                return Carbon::parse($item->date)->format('Y-m-d');
            });

        return view('user.monthly', [
            'currentMonth' => $currentMonth,
            'attendances' => $attendances,
            'user' => $user
        ]);
    }
}
