<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;

class EditController extends Controller
{
    public function show($id)
    {
        return view('admin.detail');
    } // blade構成に合わせて修正
}
