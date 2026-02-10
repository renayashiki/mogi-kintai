<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;

class StaffController extends Controller
{
    public function index()
    {
        $users = User::where('admin_status', 0)->get();

        return view('admin.staff-list', compact('users'));
    }
}
