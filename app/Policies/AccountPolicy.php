<?php

namespace App\Policies;

use App\Models\Account;
use App\Models\User;

class AccountPolicy
{
    public function view(User $user, Account $account): bool
    {
        return $account->owner_user_id === $user->id;
    }

    public function update(User $user, Account $account): bool
    {
        return $account->owner_user_id === $user->id;
    }

    public function delete(User $user, Account $account): bool
    {
        return $account->owner_user_id === $user->id;
    }

    // Optional: admin override later
    // public function viewAny(User $user): bool
    // {
    //     return true;
    // }
}