<?php

namespace App\Policies;

use App\Models\DeveloperActivity;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class DeveloperActivityPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return false;
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, DeveloperActivity $developerActivity): bool
    {
        if($developerActivity->integration){
            return $user->id === $developerActivity->integration->user_id;
        }
        if($developerActivity->user_id){
            return $user->id === $developerActivity->user_id;
        }
        return false;
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return false;
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, DeveloperActivity $developerActivity): bool
    {
        return $user->id === $developerActivity->integration->user_id;
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, DeveloperActivity $developerActivity): bool
    {
        return $user->id === $developerActivity->integration->user_id;
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, DeveloperActivity $developerActivity): bool
    {
        return false;
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, DeveloperActivity $developerActivity): bool
    {
        return false;
    }

    public function approveMultiple(User $user, array $developerActivityIds): bool
    {
        $ownedCount = DeveloperActivity::whereIn('id', $developerActivityIds)
            ->whereHas('integration.user', function ($query) use ($user) {
                $query->where('id', $user->id);
            })
            ->count();

        return $ownedCount === count($developerActivityIds);
    }
}
