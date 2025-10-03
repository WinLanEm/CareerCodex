<?php

namespace App\Http\Controllers\User;


use App\Contracts\Repositories\Email\GenerateVerificationCodeRepositoryInterface;
use App\Contracts\Repositories\User\FindUserRepositoryInterface;
use App\Http\Controllers\Controller;
use App\Http\Requests\User\ResendVerifyEmailRequest;

use App\Http\Resources\MessageResource;
use App\Repositories\Email\VerifyEmail;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;

class ResendVerifyEmailController extends Controller
{
    public function __construct(
        readonly private FindUserRepositoryInterface                 $findUserRepository,
        readonly private GenerateVerificationCodeRepositoryInterface $generateVerificationCode,
    )
    {
    }

    public function __invoke(ResendVerifyEmailRequest $request)
    {
        $user = $this->findUserRepository->findByEmail($request->get('email'));
        if ($user->hasVerifiedEmail()) {
            return new MessageResource('Email has already been verified.',false,422);
        }

        Mail::to($user->email)->send(new VerifyEmail($user,$this->generateVerificationCode));


        return new MessageResource('Verification code sent to your email address.',true);
    }
}
