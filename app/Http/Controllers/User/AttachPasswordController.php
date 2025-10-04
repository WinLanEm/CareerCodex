<?php

namespace App\Http\Controllers\User;

use App\Contracts\Repositories\User\UpdateUserRepositoryInterface;
use App\Http\Controllers\Controller;
use App\Http\Requests\User\AttachPasswordRequest;
use App\Http\Resources\MessageResource;
use Illuminate\Support\Facades\Auth;

class AttachPasswordController extends Controller
{
    public function __construct(
        readonly private UpdateUserRepositoryInterface $updateUserRepository,
    )
    {
    }

    public function __invoke(AttachPasswordRequest $request)
    {
        $this->updateUserRepository->update($request->toArray(),Auth::user());
        return new MessageResource('Password updated successfully',true,202);
    }
}
