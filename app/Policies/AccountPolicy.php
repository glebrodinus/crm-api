<?php

namespace App\Policies;

use App\Models\Account;
use App\Models\User;

class AccountPolicy
{
    public function view(User $user, Account $account): bool
    {
        return $account->created_by_user_id === $user->id;
    }

    public function update(User $user, Account $account): bool
    {
        return $account->created_by_user_id === $user->id;
    }

    public function delete(User $user, Account $account): bool
    {
        return $account->created_by_user_id === $user->id;
    }

    public function viewAny(User $user): bool
    {
        return true;
    }

    public function markReachable(User $user, Account $account): bool
    {
        return $account->created_by_user_id === $user->id;
    }

    public function markUnreachable(User $user, Account $account): bool
    {
        return $account->created_by_user_id === $user->id;
    }

    public function qualify(User $user, Account $account): bool
    {
        return $account->created_by_user_id === $user->id;
    }

    public function disqualify(User $user, Account $account): bool
    {
        return $account->created_by_user_id === $user->id;
    }

}