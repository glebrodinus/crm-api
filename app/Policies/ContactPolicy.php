<?php

namespace App\Policies;

use App\Models\Contact;
use App\Models\User;

class ContactPolicy
{
    /**
     * List contacts (controller will scope by account ownership).
     */
    public function viewAny(User $user): bool
    {
        return true;
    }

    /**
     * View a contact.
     */
    public function view(User $user, Contact $contact): bool
    {
        return $contact->account->owner_user_id === $user->id;
    }

    /**
     * Create a contact.
     * Account ownership must be validated in controller.
     */
    public function create(User $user): bool
    {
        return true;
    }

    /**
     * Update a contact.
     */
    public function update(User $user, Contact $contact): bool
    {
        return $contact->account->owner_user_id === $user->id;
    }

    /**
     * Delete a contact.
     */
    public function delete(User $user, Contact $contact): bool
    {
        return $contact->account->owner_user_id === $user->id;
    }

    /**
     * Restore (optional).
     */
    public function restore(User $user, Contact $contact): bool
    {
        return $contact->account->owner_user_id === $user->id;
    }

    /**
     * Force delete (disabled).
     */
    public function forceDelete(User $user, Contact $contact): bool
    {
        return false;
    }
}