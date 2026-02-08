<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\AttendanceCorrect;

class MyRequestController extends Controller
{
    public function index(Request $request)
    {
        // クエリパラメータから status を取得（デフォルトは pending）
        $status = $request->query('status', 'pending');

        // DB上の文言に変換
        $dbStatus = ($status === 'approved') ? '承認済み' : '承認待ち';

        // FN031, FN032: ログインユーザーの申請を、状態に応じて取得
        $requests = AttendanceCorrect::with(['user']) // リレーションをロード
            ->where('user_id', Auth::id())
            ->where('approval_status', $dbStatus)
            ->orderBy('application_date', 'desc')
            ->get();

        return view('user.requests', compact('requests', 'status'));
    }
}
