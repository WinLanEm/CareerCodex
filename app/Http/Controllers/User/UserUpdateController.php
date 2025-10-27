<?php

namespace App\Http\Controllers\User;

use App\Contracts\Repositories\User\UpdateUserRepositoryInterface;
use App\Http\Controllers\Controller;
use App\Http\Requests\User\UserUpdateRequest;
use App\Http\Resources\MessageResource;
use Illuminate\Support\Facades\Auth;

class UserUpdateController extends Controller
{
    public function __construct(
        readonly private UpdateUserRepositoryInterface $updateUserRepository,
    )
    {
    }

    public function __invoke(UserUpdateRequest $request)
    {
        $this->updateUserRepository->update($request->toArray(),Auth::user());
        return new MessageResource('Updated successfully',true,202);
    }
}
