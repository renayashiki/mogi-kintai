<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\AttendanceCorrect;

class CorrectionRequestController extends Controller
{
    /**
     * FN041: 申請一覧取得機能（管理者）
     */
    public function index(Request $request)
    {
        // クエリパラメータから status を取得（デフォルトは pending）
        $status = $request->query('status', 'pending');

        // DB上の文言に変換
        $dbStatus = ($status === 'approved') ? '承認済み' : '承認待ち';

        // 原材料（休憩申請レコード）も一緒に取得しておく
        $requests = AttendanceCorrect::with([
            'user',
            'attendanceCorrectRests' // 3回目以降の休憩データも事前にロード
        ])
            ->where('approval_status', $dbStatus)
            ->orderBy('application_date', 'desc')
            ->get();

        return view('admin.requests', compact('requests', 'status'));
    }
}
