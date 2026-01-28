<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\Note;
use App\Models\Account;
use App\Models\Contact;
use App\Models\Deal;
use App\Models\Activity;
use App\Models\Task;

class NoteController extends Controller
{
    public function store(Request $request)
    {
        $data = $request->validate([
            'noteable_type' => 'required|string',
            'noteable_id'   => 'required|integer',
            'body'          => 'required|string',
            'is_pinned'     => 'nullable|boolean',
            'is_private'    => 'nullable|boolean',
            'is_important'  => 'nullable|boolean',
        ]);

        $noteableClass = $this->resolveNoteableClass($data['noteable_type']);
        if (! $noteableClass) {
            return $this->error('Invalid noteable_type', [], 422);
        }

        $noteable = $noteableClass::findOrFail($data['noteable_id']);

        // ownership check on create (account owner only)
        if (! $this->userOwnsNoteableAccount(Auth::id(), $noteable)) {
            return $this->error('Unauthorized', [], 403);
        }

        $note = new Note([
            'body'         => $data['body'],
            'is_pinned'    => $data['is_pinned'] ?? false,
            'is_private'   => $data['is_private'] ?? false,
            'is_important' => $data['is_important'] ?? false,
        ]);

        $noteable->notes()->save($note);

        // return only note (no relations)
        return $this->success('Note created successfully', $note->refresh(), 201);
    }

    public function update(Request $request, Note $note)
    {
        // Load noteable for policy check
        $note->load('noteable');

        $this->authorize('update', $note);

        $data = $request->validate([
            'body'         => 'sometimes|required|string',
            'is_pinned'    => 'nullable|boolean',
            'is_private'   => 'nullable|boolean',
            'is_important' => 'nullable|boolean',

            // don't allow parent changes
            'noteable_id'   => 'prohibited',
            'noteable_type' => 'prohibited',
            'created_by_user_id' => 'prohibited',
        ]);

        // Optional: prevent changing creator
        unset($data['created_by_user_id']);

        // isDirty pattern (optional)
        $note->fill($data);

        if (! $note->isDirty()) {
            return $this->success('No changes', $note);
        }

        $note->save();

        return $this->success('Note updated successfully', $note->refresh());
    }

    public function destroy(Note $note)
    {
        $note->load('noteable');

        $this->authorize('delete', $note);

        if (! $note->delete()) {
            return $this->error('Failed to delete note', [], 500);
        }

        return $this->success('Note deleted successfully');
    }

    public function indexForAccount(Account $account)
    {
        $this->authorize('view', $account);

        $notes = $account->notes()
            ->orderByDesc('is_pinned')
            ->orderByDesc('created_at')
            ->get();

        $message = $notes->isEmpty()
            ? 'No notes found'
            : 'Notes retrieved successfully';

        return $this->success($message, $notes);
    }

    /**
     * Keep this simple: allow short aliases to avoid sending full class names.
     */
    private function resolveNoteableClass(string $type): ?string
    {
        return match ($type) {
            'account'  => Account::class,
            'contact'  => Contact::class,
            'deal'     => Deal::class,
            'activity' => Activity::class,
            'task'     => Task::class,
            default    => null,
        };
    }

    /**
     * Account-owner-only rule for create.
     */
    private function userOwnsNoteableAccount(int $userId, $noteable): bool
    {
        if ($noteable instanceof Account) {
            return $noteable->owner_user_id === $userId;
        }

        if (isset($noteable->account_id)) {
            return Account::whereKey($noteable->account_id)
                ->where('owner_user_id', $userId)
                ->exists();
        }

        return false;
    }
}