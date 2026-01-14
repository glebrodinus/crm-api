<?php

namespace App\Policies;

use App\Models\Note;
use App\Models\User;
use App\Models\Account;
use App\Models\Contact;
use App\Models\Deal;
use App\Models\Activity;
use App\Models\Task;

class NotePolicy
{
    public function view(User $user, Note $note): bool
    {
        return $this->ownsParentAccount($user, $note);
    }

    public function update(User $user, Note $note): bool
    {
        return $this->ownsParentAccount($user, $note);
    }

    public function delete(User $user, Note $note): bool
    {
        return $this->ownsParentAccount($user, $note);
    }

    private function ownsParentAccount(User $user, Note $note): bool
    {
        $noteable = $note->noteable;

        if (! $noteable) {
            return false;
        }

        // Note attached directly to an Account
        if ($noteable instanceof Account) {
            return $noteable->owner_user_id === $user->id;
        }

        // Note attached to models that have account_id
        if (
            $noteable instanceof Contact ||
            $noteable instanceof Deal ||
            $noteable instanceof Activity ||
            $noteable instanceof Task
        ) {
            // assumes these models have account_id
            return (int) $noteable->account_id > 0
                && Account::whereKey($noteable->account_id)
                    ->where('owner_user_id', $user->id)
                    ->exists();
        }

        return false;
    }
}