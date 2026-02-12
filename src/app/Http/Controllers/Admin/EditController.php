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
        DB::transaction(function () use ($request, $id) {
            $attendance = AttendanceRecord::findOrFail($id);
            $targetDate = $attendance->date->format('Y-m-d'); // 確実に Y-m-d 形式で取得

            // 1. 休憩データの物理的な入れ替え（原材料の更新）
            $attendance->rests()->delete();
            if ($request->rests) {
                foreach ($request->rests as $restData) {
                    if (!empty($restData['in'])) {
                        $attendance->rests()->create([
                            'rest_in'  => $targetDate . ' ' . $restData['in'],
                            'rest_out' => !empty($restData['out']) ? $targetDate . ' ' . $restData['out'] : null,
                        ]);
                    }
                }
            }

            // 2. 勤怠本体の更新
            // まず時間をセット（計算のために一旦モデルの状態を更新するが、まだ save はしない）
            $attendance->clock_in = $targetDate . ' ' . $request->clock_in;
            $attendance->clock_out = $request->clock_out ? $targetDate . ' ' . $request->clock_out : null;
            $attendance->comment = $request->comment;

            // 3. 最新の原材料（rests）に基づいて「秒」を計算し、DB保存用に整形
            $attendance->load('rests'); // 更新後の休憩を読み込み直し

            // 計算メソッドを呼び出し、DB保存用の H:i:s 形式を作る
            $totalWorkSeconds = $attendance->getWorkSeconds();
            $totalRestSeconds = $attendance->getRestSeconds();

            // 4. DBのカラム（加工品）に「正解」を書き込んで保存
            $attendance->update([
                'clock_in'  => $attendance->clock_in,
                'clock_out' => $attendance->clock_out,
                'comment'   => $attendance->comment,
                'total_rest_time' => $this->formatSecondsForDb($totalRestSeconds),
                'total_time'      => $this->formatSecondsForDb($totalWorkSeconds),
            ]);
        });

        return redirect()->route('admin.attendance.list', ['date' => AttendanceRecord::find($id)->date->format('Y-m-d')]);
    }

    /**
     * DB保存用フォーマット (H:i:s)
     */
    private function formatSecondsForDb(int $seconds): string
    {
        $h = floor($seconds / 3600);
        $m = floor(($seconds % 3600) / 60);
        $s = $seconds % 60;
        return sprintf('%02d:%02d:%02d', $h, $m, $s);
    }
}
