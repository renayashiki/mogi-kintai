<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;

class MonthlyController extends Controller
{
    public function index()
    {
        return view('user.monthly');
    }
}
