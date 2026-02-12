<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\AttendanceRecord;
use App\Models\Rest;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;

class StampController extends Controller
{
    public function index(Request $request)
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();
        $today = Carbon::today();

        // 1. まず今日の出勤レコードがあるか確認
        $todayRecord = AttendanceRecord::where('user_id', $user->id)
            ->whereDate('date', $today)
            ->first();

        // 判定フラグ
        $hasClockIn = $todayRecord ? true : false;
        $hasClockOut = ($todayRecord && $todayRecord->clock_out) ? true : false;

        // --- ここで「直後の表示」と「再ログイン後の表示」を分ける ---
        if ($request->query('status') === 'finished') {
            // 退勤ボタンを押した直後（URLに ?status=finished がある時）
            $attendanceStatus = 'finished';
        } elseif ($hasClockOut) {
            // 一度画面を離れた後や、再ログイン後（URLにクエリがない時）
            $attendanceStatus = 'outside';
        } elseif ($hasClockIn) {
            $attendanceStatus = $user->attendance_status;
        } else {
            $attendanceStatus = 'outside';
        }

        return view('user.stamp', compact('attendanceStatus', 'hasClockIn'));
    }

    public function store(Request $request)
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();
        $type = $request->input('type');
        $now = Carbon::now();
        $today = Carbon::today();

        switch ($type) {
            case 'clock_in':
                // 【重要：二重出勤防止】今日すでにレコードがある場合は何もしない
                $exists = AttendanceRecord::where('user_id', $user->id)
                    ->whereDate('date', $today)
                    ->exists();

                if ($exists) {
                    return redirect()->route('attendance.index');
                }

                AttendanceRecord::create([
                    'user_id' => $user->id,
                    'date' => $today,
                    'clock_in' => $now,
                ]);
                $user->update(['attendance_status' => 'working']);
                return redirect()->route('attendance.index');

            case 'rest_in': // 休憩開始
                $record = AttendanceRecord::where('user_id', $user->id)->whereNull('clock_out')->latest()->first();
                Rest::create([
                    'attendance_record_id' => $record->id,
                    'rest_in' => $now,
                ]);
                $user->update(['attendance_status' => 'resting']);
                return redirect()->route('attendance.index');

            case 'rest_out': // 休憩終了 (ここが修正ポイント！)
                $record = AttendanceRecord::where('user_id', $user->id)->whereNull('clock_out')->latest()->first();
                // まだ終了していない最新の休憩を取得して更新
                $rest = Rest::where('attendance_record_id', $record->id)->whereNull('rest_out')->first();
                if ($rest) {
                    $rest->update(['rest_out' => $now]);
                }
                $user->update(['attendance_status' => 'working']); // ステータスを出勤中に戻す
                return redirect()->route('attendance.index');

            case 'clock_out':
                // ① 二重退勤チェック（既存通り）
                $alreadyClockedOut = AttendanceRecord::where('user_id', $user->id)
                    ->whereDate('date', $today)
                    ->whereNotNull('clock_out')
                    ->exists();

                if ($alreadyClockedOut) {
                    return redirect()->route('attendance.index');
                }

                // ② レコード取得（既存通り）
                $record = AttendanceRecord::where('user_id', $user->id)
                    ->whereNull('clock_out')
                    ->latest()
                    ->first();

                if (!$record) {
                    $record = AttendanceRecord::where('user_id', $user->id)->whereDate('date', $today)->first();
                }

                // ③【ここから重要：原材料モードへの切り替え】
                $record->load('rests'); // 休憩をロード
                $record->clock_out = $now; // メモリ上で退勤時刻をセット（アクセサ計算用）

                // 手動の diffInSeconds は一切不要！アクセサを呼ぶだけ
                $totalRestTime = $record->total_rest_time; // モデルが勝手に H:i を出す
                $totalWorkTime = $record->total_time;      // モデルが勝手に H:i を出す

                // ④ 保存（刻印）
                $record->update([
                    'clock_out' => $now, // Carbonインスタンスのまま渡してOK（Laravelがよしなにします）
                    'total_rest_time' => $totalRestTime,
                    'total_time' => $totalWorkTime,
                ]);

                // ⑤ ユーザーステータスの更新（ここも仕様通り残っています！）
                $user->update(['attendance_status' => 'finished']);

                // ⑥ リダイレクト（ここも残っています！）
                return redirect('/attendance?status=finished');
        }
    }
}
