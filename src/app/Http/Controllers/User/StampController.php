<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;

class StampController extends Controller
{
    public function index()
    {
        return view('user.stamp');
    }
}
