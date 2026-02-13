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
    public function index()
    {
        $this->authorize('viewAny', Task::class);

        // optional: return tasks list later
        // return $this->success('Tasks retrieved successfully', Task::latest()->paginate(50));
        return $this->success('Tasks retrieved successfully');
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

            'type' => ['required', 'in:call,quote,follow_up,email,meeting,update,invoice,payment,claim'],
            'title' => ['nullable', 'string', 'max:255'],
            'priority' => ['nullable', 'integer', 'min:1', 'max:4'],

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

            'assigned_to_user_id' => ['required', 'exists:users,id'],

            'type' => ['required', 'in:call,quote,follow_up,email,meeting,update,invoice,payment,claim'],
            'title' => ['nullable', 'string', 'max:255'],
            'priority' => ['nullable', 'integer', 'min:1', 'max:4'],

            'note' => ['nullable', 'string'],

            'due_at' => ['required', 'date'],

            'completed_at' => ['nullable', 'date'],
            'completed_by_user_id' => ['nullable', 'exists:users,id'],
        ]);

        // If task is being marked completed, ensure completed_by_user_id
        if (!empty($data['completed_at']) && empty($data['completed_by_user_id'])) {
            $data['completed_by_user_id'] = Auth::id();
        }

        // If task is being un-completed, clear completed_by_user_id too
        if (array_key_exists('completed_at', $data) && empty($data['completed_at'])) {
            $data['completed_by_user_id'] = null;
        }

        $task->update($data);

        if (!$task->wasChanged()) {
            return $this->success('No changes detected', $task);
        }

        return $this->success('Task updated successfully', $task);
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