<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\AttendanceCorrect;

class CorrectionRequestController extends Controller
{
    public function index(Request $request)
    {
        $status = $request->query('status', 'pending');
        $dbStatus = ($status === 'approved') ? '承認済み' : '承認待ち';
        $requests = AttendanceCorrect::with([
            'user',
            'attendanceCorrectRests'
        ])
            ->where('approval_status', $dbStatus)
            ->orderBy('new_date', 'asc')
            ->get();
        return view('admin.requests', compact('requests', 'status'));
    }
}
