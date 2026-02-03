<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;

class WorkDetailController extends Controller
{
    public function show($id)
    {
        return view('user.detail');
    }
}
