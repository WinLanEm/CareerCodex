<?php

namespace App\Http\Controllers\DeveloperActivity;

use App\Contracts\Repositories\DeveloperActivities\DeveloperActivityDeleteRepositoryInterface;
use App\Contracts\Repositories\DeveloperActivities\DeveloperActivityFindRepositoryInterface;
use App\Http\Controllers\Controller;
use App\Http\Resources\MessageResource;
use Illuminate\Http\Request;

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
        $userId = auth()->id();
        $developerActivity = $this->findRepository->find($id,$userId);
        if(!$developerActivity){
            return new MessageResource('DeveloperActivity not found', false,404);
        }
        $res = $this->deleteRepository->delete($developerActivity);
        if(!$res){
            return new MessageResource('DeveloperActivity delete failed', false,500);
        }
        return response()->noContent();
    }
}
