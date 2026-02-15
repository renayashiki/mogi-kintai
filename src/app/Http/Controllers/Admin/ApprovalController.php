<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AttendanceCorrect;
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
            ->orderBy('new_date', 'asc')
            ->get();

        return view('admin.requests', compact('requests', 'status'));
    }

    /**
     * FN050: 申請詳細表示
     * [修正点] 申請に紐づく追加休憩(attendanceCorrectRests)を確実にロード
     */
    public function show($attendance_correct_request_id)
    {
        $correctionRequest = AttendanceCorrect::with(['user', 'attendanceCorrectRests', 'attendanceRecord'])
            ->findOrFail($attendance_correct_request_id);

        return view('admin.approve', compact('correctionRequest'));
    }

    /**
     * FN051: 承認機能
     * [修正点] DBの物理的な個数・時刻・合計時間をすべて同期させる
     */
    public function approve($attendance_correct_request_id)
    {
        $correctionRequest = AttendanceCorrect::with('attendanceCorrectRests')->findOrFail($attendance_correct_request_id);

        DB::transaction(function () use ($correctionRequest) {
            $attendance = $correctionRequest->attendanceRecord;

            // ① 本番の休憩テーブル(rests)を、申請内容で完全に入れ替える
            // これにより物理的なレコード個数が申請と一致する
            $attendance->rests()->delete();

            $newRests = [];
            if ($correctionRequest->new_rest1_in) {
                $newRests[] = ['in' => $correctionRequest->new_rest1_in, 'out' => $correctionRequest->new_rest1_out];
            }
            if ($correctionRequest->new_rest2_in) {
                $newRests[] = ['in' => $correctionRequest->new_rest2_in, 'out' => $correctionRequest->new_rest2_out];
            }
            foreach ($correctionRequest->attendanceCorrectRests as $extra) {
                $newRests[] = ['in' => $extra->new_rest_in, 'out' => $extra->new_rest_out];
            }

            foreach ($newRests as $restData) {
                $attendance->rests()->create([
                    'rest_in'  => $restData['in'],
                    'rest_out' => $restData['out'],
                ]);
            }

            // ② 最新の原材料から精密計算（モデルのメソッドを使用）
            $attendance->load('rests');
            $restSec = $attendance->getRestSeconds();
            $workSec = $attendance->getWorkSeconds();

            // ③ 本番テーブルに保存（秒付きフォーマットで統一）
            $attendance->update([
                'date'            => $correctionRequest->new_date,
                'clock_in'        => $correctionRequest->new_clock_in,
                'clock_out'       => $correctionRequest->new_clock_out,
                'total_rest_time' => $attendance->formatSecondsForDb($restSec),
                'total_time'      => $attendance->formatSecondsForDb($workSec),
                'comment'         => $correctionRequest->comment,
            ]);

            // ④ ステータス更新
            $correctionRequest->update(['approval_status' => '承認済み']);
        });

        return redirect()->route('attendance.request.list', ['status' => 'approved']);
    }
}
