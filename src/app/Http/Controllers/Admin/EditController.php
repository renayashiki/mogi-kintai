<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AttendanceRecord;
use App\Models\AttendanceCorrect; // 承認待ちチェック用
use App\Http\Requests\AdminEditRequest;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class EditController extends Controller
{
    /**
     * FN037: 詳細情報取得機能
     */
    public function show($id)
    {
        // 1. 親レコードの取得
        $attendance = AttendanceRecord::with(['rests', 'user'])->findOrFail($id);

        // 2. 承認待ちの申請（子）があるか取得
        $pendingRequest = AttendanceCorrect::where('attendance_record_id', $id)
            ->where('approval_status', '承認待ち')
            ->with('attendanceCorrectRests') // 3回目以降の休憩
            ->first();

        $hasPendingRequest = (bool)$pendingRequest;

        // 3. 承認待ちがある場合、表示用オブジェクトの中身を「申請内容」で上書きする
        if ($hasPendingRequest) {
            $attendance->clock_in = $pendingRequest->new_clock_in;
            $attendance->clock_out = $pendingRequest->new_clock_out;
            $attendance->comment = $pendingRequest->comment;

            $newRests = collect();

            // 休憩1
            if ($pendingRequest->new_rest1_in) {
                $newRests->push((object)[
                    'rest_in' => $pendingRequest->new_rest1_in,
                    'rest_out' => $pendingRequest->new_rest1_out
                ]);
            }
            // 休憩2
            if ($pendingRequest->new_rest2_in) {
                $newRests->push((object)[
                    'rest_in' => $pendingRequest->new_rest2_in,
                    'rest_out' => $pendingRequest->new_rest2_out
                ]);
            }
            // 休憩3以降
            foreach ($pendingRequest->attendanceCorrectRests as $extra) {
                $newRests->push((object)[
                    'rest_in' => $extra->new_rest_in,
                    'rest_out' => $extra->new_rest_out
                ]);
            }

            $attendance->setRelation('rests', $newRests);
        }

        return view('admin.detail', compact('attendance', 'hasPendingRequest'));
    }

    /**
     * FN040: 修正機能（管理者による直接修正）
     */
    public function update(AdminEditRequest $request, $id)
    {
        $attendance = AttendanceRecord::findOrFail($id);
        $targetDate = $attendance->date->format('Y-m-d');

        DB::transaction(function () use ($request, $attendance, $targetDate) {
            // 1. 休憩データの物理的な入れ替え（原材料の更新）
            $attendance->rests()->delete();
            if ($request->rests) {
                foreach ($request->rests as $restData) {
                    if (!empty($restData['in'])) {
                        $attendance->rests()->create([
                            'rest_in'  => \Carbon\Carbon::parse($targetDate . ' ' . $restData['in']),
                            'rest_out' => !empty($restData['out']) ? \Carbon\Carbon::parse($targetDate . ' ' . $restData['out']) : null,
                        ]);
                    }
                }
            }

            // 2. 勤怠本体の更新
            // まず時間をセット（計算のために一旦モデルの状態を更新するが、まだ save はしない）
            $attendance->clock_in = \Carbon\Carbon::parse($targetDate . ' ' . $request->clock_in);
            $attendance->clock_out = $request->clock_out ? \Carbon\Carbon::parse($targetDate . ' ' . $request->clock_out) : null;
            $attendance->comment = $request->comment;

            // 3. 精密計算
            $attendance->load('rests');
            $totalWorkSeconds = $attendance->getWorkSeconds();
            $totalRestSeconds = $attendance->getRestSeconds();

            // 4. 保存実行（formatSecondsForDb で秒付き刻印）
            $attendance->update([
                'clock_in'  => $attendance->clock_in,
                'clock_out' => $attendance->clock_out,
                'comment'   => $attendance->comment,
                'total_rest_time' => $attendance->formatSecondsForDb($totalRestSeconds),
                'total_time'      => $attendance->formatSecondsForDb($totalWorkSeconds),
            ]);
        });

        return redirect()->route('admin.attendance.list', ['date' => $targetDate]);
    }
}
