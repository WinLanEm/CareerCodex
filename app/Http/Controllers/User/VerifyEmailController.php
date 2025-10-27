<?php

namespace App\Http\Controllers\User;

use App\Contracts\Repositories\User\FindUserRepositoryInterface;
use App\Http\Controllers\Controller;
use App\Http\Requests\User\VerifyEmailRequest;
use App\Http\Resources\MessageResource;
use App\Http\Resources\User\AuthResource;
use App\Http\Resources\User\UserWrapperResource;
use Illuminate\Support\Facades\Auth;

class VerifyEmailController extends Controller
{
    public function __construct(
        readonly private FindUserRepositoryInterface $findUserRepository,
    )
    {
    }

    public function __invoke(VerifyEmailRequest $request)
    {
        $user = $this->findUserRepository->findByEmail($request->get('email'));

        if (!$user->verification_code || !$user->verification_code_expires_at) {
            return new MessageResource('User does not have a verification code.',false,422);
        }

        if ($user->verification_code !== $request->get('code')) {
            return new MessageResource('Invalid verification code.', false, 422);
        }

        if (now()->isAfter($user->verification_code_expires_at)) {
            return new MessageResource('Verification code has expired.', false, 422);
        }

        $user->email_verified_at = now();
        $user->verification_code = null;
        $user->verification_code_expires_at = null;
        $user->save();

        if ($request->has('issue_token')) {
            $token = $user->createToken('api-token')->plainTextToken;
            return new AuthResource($user, 'Email verify successfully.', $token);
        } else {
            Auth::guard('web')->login($user);
            $request->session()->regenerate();
            return new UserWrapperResource($user,true,200);
        }
    }
}
