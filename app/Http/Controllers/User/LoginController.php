<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Http\Requests\User\LoginRequest;
use App\Http\Resources\MessageResource;
use App\Http\Resources\User\AuthResource;
use App\Http\Resources\User\UserWrapperResource;
use Illuminate\Support\Facades\Auth;

class LoginController extends Controller
{
    public function __invoke(LoginRequest $request)
    {
        if (!Auth::attempt($request->only('email', 'password'))) {
            return new MessageResource('Invalid login details', false,401);
        }

        $user = Auth::user();

        if (!$user->hasVerifiedEmail()) {
            return new MessageResource('Email not verified.', false,403);
        }

        if ($request->has('issue_token')) {
            $token = $user->createToken('api-token')->plainTextToken;
            return new AuthResource($user, 'success', $token);
        } else {
            Auth::guard('web')->login($user);
            $request->session()->regenerate();
            return new UserWrapperResource($user,true,200);
        }
    }
}
