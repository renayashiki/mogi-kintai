<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;

class ApprovalController extends Controller
{
    public function index()
    {
        return view('admin.requests');
    }
    public function show($id)
    {
        return view('admin.approval');
    }
}
