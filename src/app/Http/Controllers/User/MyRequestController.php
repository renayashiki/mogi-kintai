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
        $status = $request->query('status', 'pending');
        $dbStatus = ($status === 'approved') ? '承認済み' : '承認待ち';
        $requests = AttendanceCorrect::with(['user', 'attendanceRecord.rests'])
            ->where('user_id', Auth::id())
            ->where('approval_status', $dbStatus)
            ->orderBy('new_date', 'asc')
            ->get();
        return view('user.requests', compact('requests', 'status'));
    }
}
