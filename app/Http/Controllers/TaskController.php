<?php

namespace App\Http\Controllers;

use App\Models\Task;
use App\Models\Account;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class TaskController extends Controller
{
    /**
     * Display a listing of the resource.
     */
 public function index(Request $request)
    {
        $this->authorize('viewAny', Task::class);

        $query = Task::query()
            ->with('account:id,name')
            ->where('created_by_user_id', Auth::id());

        if ($request->filled('status')) {
            if ($request->status === 'open') {
                $query->whereNull('completed_at');
            }

            if ($request->status === 'completed') {
                $query->whereNotNull('completed_at');
            }
        }

        $tasks = $query
            ->orderBy('due_at', 'asc')
            ->orderBy('id', 'desc')
            ->get();

        $message = $tasks->isEmpty()
            ? 'No tasks found'
            : 'Tasks retrieved successfully';

        return $this->success($message, $tasks);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $data = $request->validate([
            'account_id' => ['required', 'exists:accounts,id'],
            'contact_id' => ['nullable', 'exists:contacts,id'],
            'deal_id'    => ['nullable', 'exists:deals,id'],

            'type' => ['required', 'in:call,email,text,meeting,other'],
            'title' => ['nullable', 'string', 'max:255'],
            'priority' => ['nullable', 'integer', 'min:1', 'max:3'],

            'note' => ['nullable', 'string'],
            'due_at' => ['required', 'date'],
        ]);

        // 🔐 Load account + authorize
        $account = Account::findOrFail($data['account_id']);
        $this->authorize('update', $account);

        // 🎯 Default values
        $data['priority'] = $data['priority'] ?? 1;

        $task = Task::create([
            ...$data,

            // NEVER trust frontend for these
            'created_by_user_id' => Auth::id(),
            'assigned_to_user_id' => Auth::id(),

            // completion defaults
            'completed_at' => null,
            'completed_by_user_id' => null,
        ]);

        if (!$task) {
            return $this->error('Failed to create task', [], 500);
        }

        return $this->success('Task created successfully', $task, 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(Task $task)
    {
        $this->authorize('view', $task);

        return $this->success('Task retrieved successfully', $task);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Task $task)
    {
        $this->authorize('update', $task);

        $data = $request->validate([
            'contact_id' => ['nullable', 'exists:contacts,id'],
            'deal_id'    => ['nullable', 'exists:deals,id'],

            'type' => ['required', 'in:call,email,text,meeting,other'],
            'title' => ['nullable', 'string', 'max:255'],
            'priority' => ['nullable', 'integer', 'min:1', 'max:3'],

            'note' => ['nullable', 'string'],
            'due_at' => ['required', 'date'],

            // allow UI to mark complete/uncomplete
            'completed_at' => ['nullable', 'date'],
        ]);

        // default priority if missing
        $data['priority'] = $data['priority'] ?? $task->priority ?? 1;

        // ✅ completion logic (backend-controlled)
        if (!empty($data['completed_at'])) {
            $data['completed_by_user_id'] = Auth::id();
        } else {
            // if explicitly cleared/uncompleted
            if (array_key_exists('completed_at', $data)) {
                $data['completed_by_user_id'] = null;
            }
        }

        // ✅ keep assignment safe (for now assign stays to current user / existing)
        // If you want to force "always assigned to me" uncomment:
        // $data['assigned_to_user_id'] = Auth::id();

        $task->update($data);

        if (! $task->wasChanged()) {
            return $this->success('No changes detected', $task);
        }

        return $this->success('Task updated successfully', $task);
    }

    /**
     * Mark task as completed
     */
    public function complete(Task $task)
    {
        $this->authorize('update', $task);

        $task->update([
            'completed_at' => now(),
            'completed_by_user_id' => Auth::id(),
        ]);

        return $this->success('Task completed successfully', $task);
    }

    /**
     * Reopen task
     */
    public function reopen(Task $task)
    {
        $this->authorize('update', $task);

        $task->update([
            'completed_at' => null,
            'completed_by_user_id' => null,
        ]);

        return $this->success('Task reopened successfully', $task);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Task $task)
    {
        $this->authorize('delete', $task);

        $deleted = $task->delete();

        if (!$deleted) {
            return $this->error('Task could not be deleted');
        }

        return $this->success('Task deleted successfully');
    }
}