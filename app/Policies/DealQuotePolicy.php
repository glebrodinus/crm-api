<?php

namespace App\Policies;

use App\Models\DealQuote;
use App\Models\User;

class DealQuotePolicy
{
    public function view(User $user, DealQuote $quote): bool
    {
        return $quote->deal?->owner_user_id === $user->id;
    }

    public function create(User $user): bool
    {
        return true;
    }

    public function update(User $user, DealQuote $quote): bool
    {
        return $quote->deal?->owner_user_id === $user->id;
    }

    public function delete(User $user, DealQuote $quote): bool
    {
        return $quote->deal?->owner_user_id === $user->id;
    }
}