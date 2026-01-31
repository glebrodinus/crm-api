<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Activity;
use App\Models\Account;
use Illuminate\Support\Facades\Auth;

class ActivityController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $this->authorize('viewAny', Activity::class);
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

            'type'      => ['required', 'in:call,email,text,meeting'],
            'direction' => ['required', 'in:inbound,outbound'],
            'outcome'   => ['nullable', 'in:connected,attempted,failed'],

            'voicemail_left' => ['boolean'],

            'note' => ['nullable', 'string', 'max:255'],

            'contact_phone' => ['nullable', 'string', 'max:10'],
            'contact_phone_extension' => ['nullable', 'string', 'max:6'],
            'contact_email' => ['nullable', 'email', 'max:100'],

            'occurred_at' => ['required', 'date'],
        ]);

        // Load account first
        $account = Account::findOrFail($data['account_id']);

        // ACCOUNT OWNER CHECK (for now, later teams)
        $this->authorize('update', $account);

        // Create activity
        $activity = Activity::create([
            ...$data,
            'created_by_user_id' => Auth::id(),
        ]);

        if (! $activity) {
            return $this->error('Failed to create activity', [], 500);
        }

        return $this->success('Activity created successfully', $activity, 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(Activity $activity)
    {
        $this->authorize('view', $activity);

        return $this->success('Activity retrieved successfully', $activity);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Activity $activity)
    {
        // Authorize via Activity policy
        $this->authorize('update', $activity);

        $data = $request->validate([
            'type'      => ['required', 'in:call,email,text,meeting'],
            'direction' => ['required', 'in:inbound,outbound'],
            'outcome'   => ['nullable', 'in:connected,attempted,failed'],

            'voicemail_left' => ['boolean'],

            'note' => ['nullable', 'string', 'max:255'],

            'contact_phone' => ['nullable', 'string', 'max:50'],
            'contact_phone_extension' => ['nullable', 'string', 'max:10'],
            'contact_email' => ['nullable', 'email', 'max:255'],

            'occurred_at' => ['required', 'date'],
        ]);

        $activity->update($data);

        if (! $activity->wasChanged()) {
            return $this->success('No changes detected', $activity);
        }

        return $this->success('Activity updated successfully', $activity);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Activity $activity)
    {
        // 🔐 Authorize via Activity policy
        $this->authorize('delete', $activity);

        $deleted = $activity->delete();

        if (! $deleted) {
            return $this->error('Activity could not be deleted');
        }

        return $this->success('Activity deleted successfully');
    }
}
