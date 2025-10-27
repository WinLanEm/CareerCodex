<?php

namespace App\Repositories\DeveloperActivities;

use App\Contracts\Repositories\DeveloperActivities\DeveloperActivityIndexRepositoryInterface;
use App\Enums\DeveloperActivityEnum;
use App\Models\DeveloperActivity;
use Illuminate\Pagination\LengthAwarePaginator;

class DeveloperActivityIndexRepository implements DeveloperActivityIndexRepositoryInterface
{
    public function index(int $page, int $perPage,int $userId, ?DeveloperActivityEnum $type, ?bool $isApproved, ?string $startDate, ?string $endDate): LengthAwarePaginator
    {
        return DeveloperActivity
            ::with([
                'integration' => function ($query) {
                    $query->select('id','service','user_id');
                },
                'integration.user' => function ($query) {
                    $query->select('id');
                },
                'user'
            ])
            ->where(function ($query) use ($userId) {
                $query->whereHas('integration.user', function ($q) use ($userId) {
                    $q->where('id', $userId);
                })
                    ->orWhereHas('user', function ($q) use ($userId) {
                        $q->where('id', $userId);
                    });
            })
            ->when($type !== null, function ($query) use ($type) {
                return $query->where('type', $type->value);
            })->when($isApproved !== null, function ($query) use ($isApproved) {
                return $query->where('is_approved', $isApproved);
            })->when($startDate !== null, function ($query) use ($startDate) {
                return $query->whereDate('completed_at', '>=', $startDate);
            })->when($endDate !== null, function ($query) use ($endDate) {
                return $query->whereDate('completed_at', '<=', $endDate);
            })
            ->orderBy('completed_at', 'desc')
            ->paginate(
                $perPage,
                ['id','user_id','integration_id','title','repository_name','type','is_approved','completed_at','url','additions','deletions'],
                'page',
                $page
        );
    }
}
