<?php

namespace App\Http\Controllers\User;

use App\Contracts\Repositories\User\FindUserRepositoryInterface;
use App\Http\Controllers\Controller;
use App\Http\Requests\User\LoginRequest;
use App\Http\Resources\MessageResource;
use App\Http\Resources\User\AuthResource;
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

        $token = $user->createToken('api-token')->plainTextToken;

        return new AuthResource($user, 'success',$token);
    }
}
