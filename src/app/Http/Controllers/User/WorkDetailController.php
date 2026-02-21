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

        // 3. 承認済みの申請があるか取得（ボタン非表示の判定用）
        $isApproved = AttendanceCorrect::where('attendance_record_id', $id)
            ->where('approval_status', '承認済み')
            ->exists();

        // 3. 承認待ちがある場合、表示データを「申請内容」で差し替え
        if ($hasPendingRequest) {
            $attendance->clock_in = Carbon::parse($pendingRequest->new_clock_in)->second(0);
            $attendance->clock_out = $pendingRequest->new_clock_out ? Carbon::parse($pendingRequest->new_clock_out)->second(0) : null;
            $attendance->comment = $pendingRequest->comment;

            $newRests = collect();

            // 共通の休憩作成クロージャ
            $makeRest = function ($in, $out) {
                return new \App\Models\Rest(['rest_in' => \Carbon\Carbon::parse($in)->second(0), 'rest_out' => \Carbon\Carbon::parse($out)->second(0)]);
            };

            if ($pendingRequest->new_rest1_in) {
                $newRests->push($makeRest($pendingRequest->new_rest1_in, $pendingRequest->new_rest1_out));
            }
            if ($pendingRequest->new_rest2_in) {
                $newRests->push($makeRest($pendingRequest->new_rest2_in, $pendingRequest->new_rest2_out));
            }
            foreach ($pendingRequest->attendanceCorrectRests as $extra) {
                $newRests->push($makeRest($extra->new_rest_in, $extra->new_rest_out));
            }

            // 重要：リレーションを上書きすることで、ビュー側の $attendance->rests がこれに置き換わる
            $attendance->setRelation('rests', $newRests);
        }

        return view('user.detail', compact('attendance', 'hasPendingRequest', 'isApproved'));
    }

    public function update(UserEditRequest $request, $id)
    {
        $attendance = AttendanceRecord::findOrFail($id);

        // 申請データの作成
        // 原則に基づき、入力された H:i 形式をそのまま保存します
        $correctRequest = AttendanceCorrect::create([
            'attendance_record_id' => $id,
            'user_id'              => Auth::id(),
            'new_date'             => $attendance->date,
            'new_clock_in'         => $request->clock_in . ':00',
            'new_clock_out'        => $request->clock_out . ':00',
            'new_rest1_in'   => data_get($request->rests, '0.in') ? data_get($request->rests, '0.in') . ':00' : null,
            'new_rest1_out'  => data_get($request->rests, '0.out') ? data_get($request->rests, '0.out') . ':00' : null,
            'new_rest2_in'   => data_get($request->rests, '1.in') ? data_get($request->rests, '1.in') . ':00' : null,
            'new_rest2_out'  => data_get($request->rests, '1.out') ? data_get($request->rests, '1.out') . ':00' : null,
            'comment'              => $request->comment,
            'approval_status'      => '承認待ち',
            'application_date'     => now(),
        ]);

        // 3回目以降の休憩
        if (isset($request->rests) && count($request->rests) > 2) {
            // 0番目と1番目は上の create で保存済みなので、2番目以降を切り出す
            $extraRests = array_slice($request->rests, 2);
            foreach ($extraRests as $rest) {
                // 入力がある場合のみ保存
                if (!empty($rest['in'])) {
                    $correctRequest->attendanceCorrectRests()->create([
                        'new_rest_in'  => $rest['in'] . ':00',
                        'new_rest_out' => $rest['out'] . ':00',
                    ]);
                }
            }
        }

        return redirect()->route('attendance.request.list');
    }
}
