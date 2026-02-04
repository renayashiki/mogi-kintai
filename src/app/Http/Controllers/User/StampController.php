<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class StampController extends Controller
{
    /**
     * 打刻画面を表示する
     *
     * @param Request $request
     * @return \Illuminate\View\View
     */
    public function index(Request $request)
    {
        // URLパラメータ ?status=... があればそれを優先（デザイン確認用）
        // なければデフォルトで 'outside' (勤務外)
        $attendanceStatus = $request->query('status', 'outside');

        return view('user.stamp', compact('attendanceStatus'));
    }
}
