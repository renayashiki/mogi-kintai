<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\AttendanceCorrect;

class CorrectionRequestController extends Controller
{
    /**
     * 管理者：修正申請一覧（承認待ち・承認済み）
     */
    public function index(Request $request)
    {
        // クエリパラメータから status を取得（デフォルトは pending）
        $status = $request->query('status', 'pending');

        // DB上の文言に変換
        $dbStatus = ($status === 'approved') ? '承認済み' : '承認待ち';

        // 全一般ユーザーの申請を、リレーションと共に取得
        $requests = AttendanceCorrect::with(['user'])
            ->where('approval_status', $dbStatus)
            ->orderBy('application_date', 'desc')
            ->get();

        return view('admin.requests', compact('requests', 'status'));
    }
}
