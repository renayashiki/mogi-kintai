<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\AttendanceRecord;
use App\Models\Rest;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;

class StampController extends Controller
{
    public function index(Request $request)
    {
        // 開発用パラメータがあれば優先
        $attendanceStatus = $request->query('status');

        // パラメータがない場合は、Userテーブルの attendance_status（規約のDB設計）から取得
        if (!$attendanceStatus) {
            $attendanceStatus = Auth::user()->attendance_status ?? 'outside';
        }

        return view('user.stamp', compact('attendanceStatus'));
    }

    public function store(Request $request)
    {
        // 開発中のみ ミドルウェアONにしたら消す：IDが1のユーザーで強制ログインさせる（ミドルウェアOFFでも動く）
        if (!Auth::check()) {
            Auth::loginUsingId(1);
        }
        /** @var \App\Models\User $user */
        $user = Auth::user();
        $type = $request->input('type');
        $now = Carbon::now();
        $today = Carbon::today();

        switch ($type) {
            case 'clock_in':
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
                // 1. 当日のレコードを取得
                $record = AttendanceRecord::where('user_id', $user->id)->whereNull('clock_out')->latest()->first();

                if (!$record) {
                    // もし見つからなければ、仕方ないので日付で探す（予備）
                    $record = AttendanceRecord::where('user_id', $user->id)
                        ->whereDate('date', $today)
                        ->first();
                }

                $record->load('rests');

                // 2. 出勤・退勤時刻をCarbonインスタンスとして確定（秒は0に揃える）
                $clockIn = Carbon::parse($record->clock_in);
                $clockOut = $now;


                // 3. 休憩時間の合計（秒）を算出
                $totalRestSeconds = 0;
                foreach ($record->rests as $rest) {
                    if ($rest->rest_in && $rest->rest_out) {
                        $totalRestSeconds += Carbon::parse($rest->rest_out)->diffInSeconds(Carbon::parse($rest->rest_in));
                    }
                }

                // 4. 総勤務時間（秒）の計算：(退勤 - 出勤) - 休憩合計
                $staySeconds = $clockOut->diffInSeconds($clockIn);
                $totalWorkingSeconds = max(0, $staySeconds - $totalRestSeconds);


                // 5. DB保存（UI仕様に合わせ H:i:00 の形式で文字列として保存）
                $record->update([
                    'clock_out' => $clockOut->format('Y-m-d H:i:s'),
                    'total_rest_time' => gmdate('H:i:s', $totalRestSeconds),
                    'total_time' => gmdate('H:i:s', max(0, $totalWorkingSeconds)),
                ]);


                // 6. ユーザーステータスの更新
                $user->update(['attendance_status' => 'finished']);

                return redirect('/attendance?status=finished');
        }
    }
}
