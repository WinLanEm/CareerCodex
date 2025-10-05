<?php

namespace App\Http\Controllers\DeveloperActivity;

use App\Contracts\Repositories\DeveloperActivities\DeveloperActivityDeleteRepositoryInterface;
use App\Contracts\Repositories\DeveloperActivities\DeveloperActivityFindRepositoryInterface;
use App\Http\Controllers\Controller;
use App\Http\Resources\MessageResource;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class DeveloperActivityDeleteController extends Controller
{
    public function __construct(
        readonly private DeveloperActivityFindRepositoryInterface $findRepository,
        readonly private DeveloperActivityDeleteRepositoryInterface $deleteRepository
    )
    {
    }

    public function __invoke(int $id)
    {
        $developerActivity = $this->findRepository->findWithRelations($id);
        if(!$developerActivity){
            return new MessageResource('DeveloperActivity not found', false,404);
        }

        try {
            Gate::authorize('delete', $developerActivity);
        }catch(AuthorizationException $e){
            return new MessageResource('This action is unauthorized.',false,403);
        }

        $this->deleteRepository->delete($developerActivity);

        return response()->noContent();
    }
}
