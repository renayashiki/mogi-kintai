<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;

class DailyController extends Controller
{
    public function index()
    {
        return view('admin.daily');
    }
}
