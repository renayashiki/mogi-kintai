<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;

class MyRequestController extends Controller
{
    public function index()
    {
        return view('user.requests');
    }
}
