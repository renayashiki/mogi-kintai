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

        return view('user.detail', compact('attendance', 'hasPendingRequest'));
    }

    public function update(UserEditRequest $request, $id)
    {
        // 1. $attendance を取得（ここが全ての基準になります）
        $attendance = AttendanceRecord::with('rests')->findOrFail($id);

        // 2. メインの申請データ作成
        // $attendance の値をベースに、$request で上書きする形にします
        $correctRequest = AttendanceCorrect::create([
            'attendance_record_id' => $id,
            'user_id'              => Auth::id(),
            'new_date'             => $attendance->date, // 元のレコードの日付をセット
            'new_clock_in'         => $request->clock_in, // ここでマッピング
            'new_clock_out'        => $request->clock_out,
            'new_rest1_in'         => $request->rests[0]['in'] ?? null,
            'new_rest1_out'        => $request->rests[0]['out'] ?? null,
            'new_rest2_in'         => $request->rests[1]['in'] ?? null,
            'new_rest2_out'        => $request->rests[1]['out'] ?? null,
            'comment'              => $request->comment,
            'approval_status'      => '承認待ち',
            'application_date'     => now()->format('Y-m-d'), // シーダーに合わせて追加
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

        return redirect()->route('attendance.detail', ['id' => $id]);
    }
}
