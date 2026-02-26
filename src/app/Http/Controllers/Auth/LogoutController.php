<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class LogoutController extends Controller
{
    public function logout(Request $request)
    {
        if (Auth::guard('admin')->check()) {
            Auth::guard('admin')->logout();
            return $this->processSessionAndRedirect($request, 'admin.login');
        }
        Auth::logout();
        return $this->processSessionAndRedirect($request, 'login');
    }

    private function processSessionAndRedirect(Request $request, string $routeName)
    {
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        return redirect()->route($routeName);
    }
}
