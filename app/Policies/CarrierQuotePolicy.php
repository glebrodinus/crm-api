<?php

namespace App\Policies;

use App\Models\CarrierQuote;
use App\Models\User;

class CarrierQuotePolicy
{
    /**
     * View any carrier quotes (scoped via deal in controller)
     */
    public function viewAny(User $user): bool
    {
        return true;
    }

    /**
     * View a specific carrier quote
     */
    public function view(User $user, CarrierQuote $carrierQuote): bool
    {
        return $carrierQuote->deal?->owner_user_id === $user->id;
    }

    /**
     * Create (checked via deal in controller)
     */
    public function create(User $user): bool
    {
        return true;
    }

    /**
     * Update
     */
    public function update(User $user, CarrierQuote $carrierQuote): bool
    {
        return $carrierQuote->deal?->owner_user_id === $user->id;
    }

    /**
     * Delete
     */
    public function delete(User $user, CarrierQuote $carrierQuote): bool
    {
        return $carrierQuote->deal?->owner_user_id === $user->id;
    }
}