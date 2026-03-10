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
    public function index(Request $request)
    {
        $data = $request->validate([
            'noteable_type' => 'required|string',
            'noteable_id'   => 'required|integer',
        ]);

        $noteableClass = $this->resolveNoteableClass($data['noteable_type']);

        if (! $noteableClass) {
            return $this->error('Invalid noteable_type', [], 422);
        }

        $noteable = $noteableClass::findOrFail($data['noteable_id']);

        $this->authorize('view', $noteable);

        $notes = $noteable->notes()
            ->orderByDesc('is_pinned')
            ->orderByDesc('created_at')
            ->get();

        $links = $noteable->links()
            ->orderByDesc('is_pinned')
            ->orderByDesc('created_at')
            ->get();

        return $this->success(
            ucfirst($data['noteable_type']) . ' notes retrieved successfully',
            [
                'notes' => $notes,
                'links' => $links,
            ]
        );
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'noteable_type' => 'required|string',
            'noteable_id'   => 'required|integer',

            'type'          => 'required|in:note,link',
            'content'       => 'nullable|string|required_if:type,note',
            'url'           => 'nullable|url|required_if:type,link',
            'url_label'     => 'nullable|string|max:255',

            'is_pinned'     => 'nullable|boolean',
            'is_private'    => 'nullable|boolean',
            'is_important'  => 'nullable|boolean',
        ]);

        $noteableClass = $this->resolveNoteableClass($data['noteable_type']);

        if (! $noteableClass) {
            return $this->error('Invalid noteable_type', [], 422);
        }

        $noteable = $noteableClass::findOrFail($data['noteable_id']);

        if (! $this->userOwnsNoteableAccount(Auth::id(), $noteable)) {
            return $this->error('Unauthorized', [], 403);
        }

        $note = new Note([
            'type'         => $data['type'],
            'content'      => $data['content'] ?? null,
            'url'          => $data['url'] ?? null,
            'url_label'    => $data['url_label'] ?? null,
            'is_pinned'    => $data['is_pinned'] ?? false,
            'is_private'   => $data['is_private'] ?? false,
            'is_important' => $data['is_important'] ?? false,
        ]);

        $noteable->allNotes()->save($note);

        return $this->success(
            ucfirst($note->type) . ' created successfully',
            $note->refresh(),
            201
        );
    }

    public function update(Request $request, Note $note)
    {
        $note->load('noteable');

        $this->authorize('update', $note);

        $data = $request->validate([
            'type'         => 'sometimes|required|in:note,link',
            'content'      => 'nullable|string',
            'url'          => 'nullable|url',
            'url_label'    => 'nullable|string|max:255',

            'is_pinned'    => 'nullable|boolean',
            'is_private'   => 'nullable|boolean',
            'is_important' => 'nullable|boolean',

            'noteable_id'        => 'prohibited',
            'noteable_type'      => 'prohibited',
            'created_by_user_id' => 'prohibited',
        ]);

        $note->fill($data);

        if (! $note->isDirty()) {
            return $this->success('No changes', $note);
        }

        $note->save();

        return $this->success(
            ucfirst($note->type) . ' updated successfully',
            $note->refresh()
        );
    }

    public function destroy(Note $note)
    {
        $note->load('noteable');

        $this->authorize('delete', $note);

        if (! $note->delete()) {
            return $this->error('Failed to delete', [], 500);
        }

        return $this->success(
            ucfirst($note->type) . ' deleted successfully'
        );
    }

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

    private function userOwnsNoteableAccount(int $userId, $noteable): bool
    {
        if ($noteable instanceof Account) {
            return $noteable->created_by_user_id === $userId;
        }

        if (isset($noteable->account_id)) {
            return Account::whereKey($noteable->account_id)
                ->where('created_by_user_id', $userId)
                ->exists();
        }

        return false;
    }
}