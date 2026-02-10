<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AttendanceCorrect;
use App\Models\AttendanceRecord;
use App\Models\Rest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ApprovalController extends Controller
{
    /**
     * FN049: 申請一覧表示
     */
    public function index(Request $request)
    {
        $status = $request->query('status', 'pending');
        // ここで DB の値「承認待ち」と正確に一致させる
        $dbStatus = ($status === 'approved') ? '承認済み' : '承認待ち';

        $requests = AttendanceCorrect::with(['user'])
            ->where('approval_status', $dbStatus)
            ->orderBy('application_date', 'desc')
            ->get();

        return view('admin.requests', compact('requests', 'status'));
    }

    /**
     * FN050: 申請詳細表示
     */
    public function show($attendance_correct_request_id)
    {
        $correctionRequest = AttendanceCorrect::with(['user', 'attendanceCorrectRests'])
            ->findOrFail($attendance_correct_request_id);

        return view('admin.approve', compact('correctionRequest'));
    }

    /**
     * FN051: 承認機能（休憩1-2カラム＋3以降別テーブル対応版）
     */
    public function approve($attendance_correct_request_id)
    {
        // 修正案のロジックを統合した完成版
        $correctionRequest = AttendanceCorrect::with('attendanceCorrectRests')->findOrFail($attendance_correct_request_id);

        DB::transaction(function () use ($correctionRequest) {
            // ① 勤怠レコード本体の更新（リレーションを利用）
            $attendance = $correctionRequest->attendanceRecord;

            $attendance->update([
                'date'      => $correctionRequest->new_date,
                'clock_in'  => $correctionRequest->new_clock_in,
                'clock_out' => $correctionRequest->new_clock_out,
                'comment'   => $correctionRequest->comment,
                // [原則] 計算は精密に、表示はシンプルに。
                // 合計時間はアクセサで再計算させるため、ここではnull更新またはそのままにします
                'total_time' => null,
                'total_rest_time' => null,
            ]);

            // ② 既存の休憩をリセット
            $attendance->rests()->delete();

            // ③ 申請された休憩データを本番（restsテーブル）へ移行
            // 休憩1
            if ($correctionRequest->new_rest1_in) {
                $attendance->rests()->create([
                    'rest_in'  => $correctionRequest->new_rest1_in,
                    'rest_out' => $correctionRequest->new_rest1_out,
                ]);
            }
            // 休憩2
            if ($correctionRequest->new_rest2_in) {
                $attendance->rests()->create([
                    'rest_in'  => $correctionRequest->new_rest2_in,
                    'rest_out' => $correctionRequest->new_rest2_out,
                ]);
            }
            // 休憩3以降
            foreach ($correctionRequest->attendanceCorrectRests as $extra) {
                $attendance->rests()->create([
                    'rest_in'  => $extra->new_rest_in,
                    'rest_out' => $extra->new_rest_out,
                ]);
            }

            // ④ ステータス更新
            $correctionRequest->update(['approval_status' => '承認済み']);
        });

        return redirect()->route('attendance.request.list', ['status' => 'approved']);
    }
}
