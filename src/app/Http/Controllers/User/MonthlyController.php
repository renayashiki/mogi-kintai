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

        // 1. まずはDBから精密にデータを取得
        $attendances = AttendanceRecord::where('user_id', $user->id)
            ->whereYear('date', $currentMonth->year)
            ->whereMonth('date', $currentMonth->month)
            ->orderBy('date', 'asc')
            ->get();

        // 2. 【ここが重要】西さんの2023年6月だけ、見本通りにデータを間引く（出し分け）
        if ($user->name === '西 伶奈' && $currentMonth->format('Y-m') === '2023-06') {
            // 見本画像で「空白」になっている日にちを指定（例：3, 4, 10, 11日など）
            $offDays = ['2023-06-04', '2023-06-07', '2023-06-17', '2023-06-25'];

            $attendances = $attendances->reject(function ($record) use ($offDays) {
                // 1. $record->dateを文字列に変換して $dateStr に代入
                $dateStr = ($record->date instanceof \Carbon\Carbon)
                    ? $record->date->format('Y-m-d')
                    : $record->date;

                // 2. その $dateStr が $offDays に含まれているかチェック
                // ここで $dateStr を使うことで、エディタの「透明」も解消されるはずです
                return in_array($dateStr, $offDays);
            });
        }

        // 3. 表示用に keyBy する
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
