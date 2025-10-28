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
        if ($request->user()) {
            $request->user()->tokens()->delete();
        }

        if (Auth::guard('web')->check()) {
            Auth::guard('web')->logout();
        }

        return new MessageResource('Logged out successfully.',true,200);
    }
}
