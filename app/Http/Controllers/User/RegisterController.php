<?php

namespace App\Http\Controllers\User;

use App\Contracts\Repositories\Email\GenerateVerificationCodeRepositoryInterface;
use App\Contracts\Repositories\User\CreateUserRepositoryInterface;
use App\Contracts\Repositories\User\FindUserRepositoryInterface;
use App\Http\Controllers\Controller;
use App\Http\Requests\User\RegisterRequest;
use App\Http\Resources\MessageResource;
use App\Repositories\Email\VerifyEmail;
use Illuminate\Support\Facades\Mail;

class RegisterController extends Controller
{
    public function __construct(
        readonly private CreateUserRepositoryInterface $createUserRepository,
        readonly private GenerateVerificationCodeRepositoryInterface $generateVerificationCodeRepository,
        readonly private FindUserRepositoryInterface $findUserRepository,
    )
    {
    }

    public function __invoke(RegisterRequest $request)
    {
        $user = $this->findUserRepository->findByEmail($request->get('email'));

        if(isset($user->provider) && $user->password){
            return new MessageResource("Email already exists.",false,401);
        }else if(isset($user->provider) && !$user->password){
            return new MessageResource("Registration failed. This email is already registered with $user->provider. Try login with $user->provider and attach password",false,401);
        }

        if($user){
            return new MessageResource("Email already exists.",false,401);
        }
        $user = $this->createUserRepository->create(
            $request->get('email'),
            $request->get('password'),
            $request->get('name'),
        );

        if(!$user){
            return new MessageResource('User registered failed. Try later.',false,500);
        }

        Mail::to($user->email)->send(new VerifyEmail($user,$this->generateVerificationCodeRepository));

        return new MessageResource('User registered successfully. Please check your email for verification.',true,201);
    }
}
