<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Models\AttendanceRecord;
use App\Models\AttendanceCorrect;
use App\Http\Requests\UserEditRequest;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;

class WorkDetailController extends Controller
{
    public function show($id)
    {
        // 休憩リレーションも含めて取得
        $attendance = AttendanceRecord::with('rests')->findOrFail($id);

        if ($attendance->user_id !== Auth::id()) {
            abort(403);
        }

        // 承認待ち申請の有無を確認（画像2枚目の出し分け用）
        $hasPendingRequest = AttendanceCorrect::where('attendance_record_id', $id)
            ->where('approval_status', '承認待ち')
            ->exists();

        return view('user.detail', compact('attendance', 'hasPendingRequest'));
    }

    public function update(UserEditRequest $request, $id)
    {
        // 1. $attendance を取得（ここが全ての基準になります）
        $attendance = AttendanceRecord::with('rests')->findOrFail($id);

        // 2. メインの申請データ作成
        // $attendance の値をベースに、$request で上書きする形にします
        $correctRequest = AttendanceCorrect::create([
            'attendance_record_id' => $attendance->id, // $attendance を活用
            'user_id'              => Auth::id(),
            'new_clock_in'        => $request->new_clock_in,
            'new_clock_out'       => $request->new_clock_out,

            // 休憩1・2：リクエストの配列から取得。なければ元の値を参照する
            'new_rest1_in'        => $request->rests[0]['in'] ?? null,
            'new_rest1_out'       => $request->rests[0]['out'] ?? null,
            'new_rest2_in'        => $request->rests[1]['in'] ?? null,
            'new_rest2_out'       => $request->rests[1]['out'] ?? null,

            'comment'             => $request->comment,
            'approval_status'     => '承認待ち',
        ]);

        // 3. 3回目以降の休憩の保存
        // View側で $attendance->rests から生成された 3番目以降の入力欄を処理
        if (isset($request->rests) && count($request->rests) > 2) {
            // インデックス2以降（休憩3〜）をスライス
            $extraRests = array_slice($request->rests, 2);

            foreach ($extraRests as $rest) {
                // 入力（in）がある場合のみ、子テーブルに保存
                if (!empty($rest['in'])) {
                    $correctRequest->attendanceCorrectRests()->create([
                        'new_rest_in'  => $rest['in'],
                        'new_rest_out' => $rest['out'],
                    ]);
                }
            }
        }

        return redirect()->route('attendance.request.list')->with('success', '修正申請を提出しました。');
    }
}
