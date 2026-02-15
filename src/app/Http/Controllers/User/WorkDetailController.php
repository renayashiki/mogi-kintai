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
            $attendance->clock_in = Carbon::parse($pendingRequest->new_clock_in);
            $attendance->clock_out = $pendingRequest->new_clock_out ? Carbon::parse($pendingRequest->new_clock_out) : null;
            $attendance->comment = $pendingRequest->comment;

            $newRests = collect();

            // 共通の休憩作成クロージャ
            $makeRest = function ($in, $out) {
                return new \App\Models\Rest(['rest_in' => $in, 'rest_out' => $out]);
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
            'new_clock_in'         => $request->clock_in,
            'new_clock_out'        => $request->clock_out,
            'new_rest1_in'         => $request->rests[0]['in'] ?? null,
            'new_rest1_out'        => $request->rests[0]['out'] ?? null,
            'new_rest2_in'         => $request->rests[1]['in'] ?? null,
            'new_rest2_out'        => $request->rests[1]['out'] ?? null,
            'comment'              => $request->comment,
            'approval_status'      => '承認待ち',
            'application_date'     => now(),
        ]);

        // 3回目以降の休憩
        if (isset($request->rests) && count($request->rests) > 2) {
            $extraRests = array_slice($request->rests, 2);
            foreach ($extraRests as $rest) {
                if (!empty($rest['in'])) {
                    $correctRequest->attendanceCorrectRests()->create([
                        'new_rest_in'  => $rest['in'],
                        'new_rest_out' => $rest['out'],
                    ]);
                }
            }
        }

        return redirect()->route('attendance.request.list');
    }
}
