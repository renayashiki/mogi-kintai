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

            // 共通の休憩作成クロージャ：(object)ではなく、本物の Rest モデルを生成する
            // こうすることで getRestSeconds() の計算ロジックが正しく動く
            $makeRest = function ($in, $out) {
                return new \App\Models\Rest([
                    'rest_in'  => \Carbon\Carbon::parse($in)->second(0),
                    'rest_out' => \Carbon\Carbon::parse($out)->second(0)
                ]);
            };

            // 休憩1
            if ($pendingRequest->new_rest1_in) {
                $newRests->push($makeRest($pendingRequest->new_rest1_in, $pendingRequest->new_rest1_out));
            }
            // 休憩2
            if ($pendingRequest->new_rest2_in) {
                $newRests->push($makeRest($pendingRequest->new_rest2_in, $pendingRequest->new_rest2_out));
            }
            // 休憩3以降
            foreach ($pendingRequest->attendanceCorrectRests as $extra) {
                $newRests->push($makeRest($extra->new_rest_in, $extra->new_rest_out));
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
                    if (!empty($restData['in']) && !empty($restData['out'])) {
                        $attendance->rests()->create([
                            'rest_in'  => Carbon::parse($targetDate . ' ' . $restData['in'])->second(0),
                            'rest_out' => Carbon::parse($targetDate . ' ' . $restData['out'])->second(0),
                        ]);
                    }
                }
            }

            // 2. 勤怠本体の更新
            // まず時間をセット（計算のために一旦モデルの状態を更新するが、まだ save はしない）
            $attendance->clock_in = Carbon::parse($targetDate . ' ' . $request->clock_in)->second(0);
            $attendance->clock_out = $request->clock_out ? \Carbon\Carbon::parse($targetDate . ' ' . $request->clock_out)->second(0) : null;
            $attendance->comment = $request->comment;

            // 3. 精密計算
            $attendance->load('rests');
            $totalWorkSeconds = $attendance->getWorkSeconds();
            $totalRestSeconds = $attendance->getRestSeconds();

            // 4. 保存実行（formatSecondsForDb で秒付き刻印）
            $attendance->update([
                'clock_in'  => $attendance->clock_in->second(0),
                'clock_out' => optional($attendance->clock_out)->second(0),
                'comment'   => $attendance->comment,
                'total_rest_time' => $attendance->formatSecondsForDb($totalRestSeconds),
                'total_time'      => $attendance->formatSecondsForDb($totalWorkSeconds),
            ]);
        });

        return redirect()->route('admin.attendance.list', ['date' => $targetDate]);
    }
}
