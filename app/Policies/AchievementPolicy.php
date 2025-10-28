<?php

namespace App\Policies;

use App\Models\Achievement;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class AchievementPolicy
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
    public function view(User $user, Achievement $achievement): bool
    {
        if ($achievement->user_id) {
            return $user->id === $achievement->user_id;
        }

        if ($achievement->integration_instance_id) {
            return $user->id === $achievement->integrationInstance?->integration?->user_id;
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
    public function update(User $user, Achievement $achievement): bool
    {
        if ($achievement->user_id) {
            return $user->id === $achievement->user_id;
        }

        if ($achievement->integration_instance_id) {
            return $user->id === $achievement->integrationInstance?->integration?->user_id;
        }

        return false;
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, Achievement $achievement): bool
    {

        if ($achievement->user_id) {
            return $user->id === $achievement->user_id;
        }

        if ($achievement->integration_instance_id) {
            return $user->id === $achievement->integrationInstance?->integration?->user_id;
        }

        return false;
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, Achievement $achievement): bool
    {
        return false;
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, Achievement $achievement): bool
    {
        return false;
    }

    public function approveMultiple(User $user, array $achievementIds): bool
    {
        if (empty($achievementIds)) {
            return false;
        }

        $ownedCount = Achievement::whereIn('id', $achievementIds)
            ->where(function ($query) use ($user) {
                $query->where('user_id', $user->id)
                ->orWhereHas('integrationInstance.integration', function ($q) use ($user) {
                    $q->where('user_id', $user->id);
                });
            })
            ->count();

        return $ownedCount === count($achievementIds);
    }
}
