<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Account;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;

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
            // Account fields
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

            // Optional contact fields
            'contact_first_name' => 'nullable|string|max:50',
            'contact_last_name'  => 'nullable|string|max:50',
            'contact_phone'      => 'nullable|string|max:20',
            'contact_email'      => 'nullable|email|max:255',
        ]);

        // Create account
        $account = Account::create($data);
        $account->refresh();

        if (! $account) {
            return $this->error('Failed to create account', [], 500);
        }

        if (! empty($data['contact_first_name'])) {
            $account->contacts()->create([
                'first_name'         => $data['contact_first_name'],
                'last_name'          => $data['contact_last_name'] ?? null,
                'phone'              => $data['contact_phone'] ?? null,
                'phone_extension'    => $data['contact_phone_extension'] ?? null,
                'mobile'             => $data['contact_mobile'] ?? null,
                'email'              => $data['contact_email'] ?? null,
            ]);

            if($account){
                $account->load('contacts');
            }
        }

        return $this->success('Account created successfully', $account, 201);
    }

    /**
     * Display the specified resource.
     */

    public function show(Account $account)
    {
        if (Gate::denies('view', $account)) {
            return $this->error('Account not found', [], 404);
        }

        $account->load(['contacts', 'deals', 'activities', 'tasks', 'notes']);

        return $this->success('Account retrieved successfully', $account);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Account $account)
    {
        if (Gate::denies('update', $account)) {
            return $this->error('Account not found', [], 404);
        }

        $data = $request->validate([
            'name'    => 'required|string|max:50',
            'website' => 'nullable|string|max:255',
            'city'    => 'nullable|string|max:100',
            'state'   => 'nullable|string|max:50',
            'zip'     => 'nullable|string|max:20',
            'status'  => 'nullable|string|max:50',
            'address' => 'nullable|string|max:255',
            'address_2'=> 'nullable|string|max:255',
            'country' => 'nullable|string|max:3',
            'phone'   => 'nullable|string|max:20',
        ]);

        // Optional security: never allow these from mass update
        unset(
            $data['created_by_user_id'], 
            $data['blocked_by_user_id'], 
            $data['blocked_at'],
            // Recommended: do NOT allow owner change via normal update
            $data['owner_user_id']
        );

        $account->fill($data);

        if (! $account->isDirty()) {
            return $this->success('No changes', $account);
        }

        $saved = $account->save();

        if (! $saved) {
            return $this->error('Failed to update account', [], 500);
        }

        return $this->success('Account updated successfully', $account);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Account $account)
    {
        if (Gate::denies('delete', $account)) {
            return $this->error('Account not found', [], 404);
        }

        $deleted = $account->delete();

        if (! $deleted) {
            return $this->error('Failed to delete account', [], 500);
        }

        return $this->success('Account deleted successfully');
    }
}
