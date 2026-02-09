<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AttendanceRecord;
use App\Models\AttendanceCorrect; // 承認待ちチェック用
use App\Http\Requests\AdminEditRequest;
use Illuminate\Support\Facades\DB;

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
        // リレーションがあれば $attendance->attendanceCorrects()->where(...) でも可
        $pendingRequest = AttendanceCorrect::where('attendance_record_id', $id)
            ->where('approval_status', '承認待ち')
            ->with('attendanceCorrectRests') // 3回目以降の休憩
            ->first();

        $hasPendingRequest = (bool)$pendingRequest;

        // 3. 承認待ちがある場合、表示用オブジェクトの中身を「申請内容」で上書きする
        if ($hasPendingRequest) {
            // 基本情報の上書き
            $attendance->clock_in = $pendingRequest->new_clock_in;
            $attendance->clock_out = $pendingRequest->new_clock_out;
            $attendance->comment = $pendingRequest->comment;

            // 休憩データの差し替え（Bladeで @foreach ($attendance->rests ...) している箇所のため）
            // AttendanceCorrectにある new_rest1, new_rest2 と、
            // AttendanceCorrectRestsにある 3回目以降を統合して、擬似的なCollectionを作る
            $newRests = collect();

            // 休憩1 (new_rest1_in/out)
            if ($pendingRequest->new_rest1_in) {
                $newRests->push((object)[
                    'rest_in' => $pendingRequest->new_rest1_in,
                    'rest_out' => $pendingRequest->new_rest1_out
                ]);
            }
            // 休憩2 (new_rest2_in/out)
            if ($pendingRequest->new_rest2_in) {
                $newRests->push((object)[
                    'rest_in' => $pendingRequest->new_rest2_in,
                    'rest_out' => $pendingRequest->new_rest2_out
                ]);
            }
            // 休憩3以降 (attendance_correct_restsテーブル)
            foreach ($pendingRequest->attendanceCorrectRests as $extra) {
                $newRests->push((object)[
                    'rest_in' => $extra->new_rest_in,
                    'rest_out' => $extra->new_rest_out
                ]);
            }

            // Bladeのループで使う $attendance->rests を申請内容に丸ごと入れ替える
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

            // 1. 勤怠本体を直接更新（FN040-2: 一般ユーザーの勤怠情報としても反映）
            $attendance->update([
                'clock_in'  => $request->clock_in,
                'clock_out' => $request->clock_out,
                'comment'   => $request->comment, // DBはNULL許容だが、Requestで必須にしている
            ]);

            // 2. 休憩データの更新（既存を消して再登録が最も安全）
            $attendance->rests()->delete();

            if ($request->rests) {
                foreach ($request->rests as $restData) {
                    if (!empty($restData['in'])) {
                        $attendance->rests()->create([
                            'rest_in'  => $restData['in'],
                            'rest_out' => $restData['out'],
                        ]);
                    }
                }
            }
        });

        // 修正後は一覧画面へリダイレクト
        return redirect()->route('admin.attendance.list');
    }
}
