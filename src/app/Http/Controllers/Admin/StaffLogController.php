<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;

class StaffLogController extends Controller
{
    public function index($id)
    {
        return view('admin.staff-log');
    }
}
