<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Http\Resources\MessageResource;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class LogoutController extends Controller
{
    public function __invoke(Request $request)
    {
        Auth::guard('web')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        if ($user = $request->user()) {
            $user->tokens()->delete();
        }
        return new MessageResource('Logged out successfully.',true,200);
    }
}
