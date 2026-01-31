<?php

namespace App\Policies;

use App\Models\Activity;
use App\Models\User;

class ActivityPolicy
{
    /**
     * View list of activities
     */
    public function viewAny(User $user): bool
    {
        return true;
    }

    /**
     * View a single activity
     */
    public function view(User $user, Activity $activity): bool
    {
        return $activity->account?->owner_user_id === $user->id;
    }

    /**
     * Create is authorized via Account (not here)
     */
    public function create(User $user): bool
    {
        return true;
    }

    /**
     * Update activity
     */
    public function update(User $user, Activity $activity): bool
    {
        return $activity->account?->owner_user_id === $user->id;
    }

    /**
     * Delete activity
     */
    public function delete(User $user, Activity $activity): bool
    {
        return $activity->account?->owner_user_id === $user->id;
    }
}