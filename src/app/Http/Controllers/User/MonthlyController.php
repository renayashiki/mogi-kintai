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

        $monthStr = $request->query('month', Carbon::now()->format('Y-m'));
        $currentMonth = Carbon::parse($monthStr)->startOfMonth();

        // ★修正：with('rests') を追加して原材料を読み込んでおく
        $attendances = AttendanceRecord::with('rests')
            ->where('user_id', $user->id)
            ->whereYear('date', $currentMonth->year)
            ->whereMonth('date', $currentMonth->month)
            ->orderBy('date', 'asc')
            ->get();

        $attendances = $attendances->keyBy(function ($item) {
            return Carbon::parse($item->date)->format('Y-m-d');
        });

        return view('user.monthly', [
            'currentMonth' => $currentMonth,
            'attendances' => $attendances,
            'user' => $user
        ]);
    }
}
