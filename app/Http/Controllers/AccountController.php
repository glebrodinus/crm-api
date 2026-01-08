<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Account;
use Illuminate\Support\Facades\Auth;

class AccountController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $accounts = Account::where('owner_user_id', Auth::id())->get();

        $message = $accounts->isEmpty()
            ? 'No accounts found'
            : 'Accounts retrieved successfully';

        return $this->success($message, $accounts);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $data = $request->validate([
            'name'      => 'required|string|max:50',
            'website'   => 'nullable|string|max:255',
            'city'      => 'nullable|string|max:100',
            'state'     => 'nullable|string|max:50',
            'zip'       => 'nullable|string|max:20',
            'status'    => 'nullable|string|max:50',
            'address'   => 'nullable|string|max:255',
            'address_2' => 'nullable|string|max:255',
            'country'   => 'nullable|string|max:3',
            'phone'     => 'nullable|string|max:20',

            // Optional initial contact
            'contact_first_name' => 'nullable|string|max:50',
            'contact_last_name'  => 'nullable|string|max:50',
            'contact_phone'      => 'nullable|string|max:20',
            'contact_email'      => 'nullable|email|max:255',
        ]);

        $account = Account::create($data);

        if (! $account) {
            return $this->error('Failed to create account', [], 500);
        }

        // Optional: create first contact
        if (! empty($data['contact_first_name'])) {
            $account->contacts()->create([
                'first_name' => $data['contact_first_name'],
                'last_name'  => $data['contact_last_name'] ?? null,
                'phone'      => $data['contact_phone'] ?? null,
                'email'      => $data['contact_email'] ?? null,
            ]);

            $account->load('contacts');
        }

        return $this->success('Account created successfully', $account, 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(Account $account)
    {
        $this->authorize('view', $account);

        $account->load(['contacts', 'deals', 'activities', 'tasks', 'notes']);

        return $this->success('Account retrieved successfully', $account);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Account $account)
    {
        $this->authorize('update', $account);

        $data = $request->validate([
            'name'      => 'required|string|max:50',
            'website'   => 'nullable|string|max:255',
            'city'      => 'nullable|string|max:100',
            'state'     => 'nullable|string|max:50',
            'zip'       => 'nullable|string|max:20',
            'status'    => 'nullable|string|max:50',
            'address'   => 'nullable|string|max:255',
            'address_2' => 'nullable|string|max:255',
            'country'   => 'nullable|string|max:3',
            'phone'     => 'nullable|string|max:20',
        ]);

        // Never allow ownership or security fields to be updated here
        unset(
            $data['owner_user_id'],
            $data['created_by_user_id'],
            $data['blocked_by_user_id'],
            $data['blocked_at']
        );

        $account->fill($data);

        if (! $account->isDirty()) {
            return $this->success('No changes', $account);
        }

        $account->save();

        return $this->success('Account updated successfully', $account);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Account $account)
    {
        $this->authorize('delete', $account);

        $account->delete();

        return $this->success('Account deleted successfully');
    }
}