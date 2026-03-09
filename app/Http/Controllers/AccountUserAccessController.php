<?php

namespace App\Http\Controllers;

use App\Models\Account;
use App\Models\AccountUserAccess;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class AccountUserAccessController extends Controller
{
    public function index(Account $account)
    {
        if ($account->created_by_user_id !== Auth::id()) {
            return $this->error('You do not have permission to view account access.');
        }

        $access = $account->userAccesses()
            ->with('user:id,first_name,last_name,email')
            ->get();

        return $this->success('Account access retrieved successfully', $access);
    }

    public function store(Request $request, Account $account)
    {
        if ($account->created_by_user_id !== Auth::id()) {
            return $this->error('You do not have permission to grant account access.');
        }

        $validated = $request->validate([
            'user_id' => [
                'required',
                'integer',
                'exists:users,id',
                Rule::unique('account_user_access', 'user_id')
                    ->where(fn ($q) => $q->where('account_id', $account->id)),
            ],
            'can_edit' => ['nullable', 'boolean'],
        ]);

        if ($validated['user_id'] == $account->created_by_user_id) {
            return $this->error('Account owner already has full access.');
        }

        $access = AccountUserAccess::create([
            'account_id' => $account->id,
            'user_id' => $validated['user_id'],
            'can_edit' => $validated['can_edit'] ?? false,
        ]);

        $access->load('user:id,first_name,last_name,email');

        return $this->success('Account access granted successfully', $access);
    }

    public function update(Request $request, Account $account, AccountUserAccess $accountUserAccess)
    {
        if ($account->created_by_user_id !== Auth::id()) {
            return $this->error('You do not have permission to update access.');
        }

        if ($accountUserAccess->account_id !== $account->id) {
            return $this->error('Access record not found.');
        }

        $validated = $request->validate([
            'can_edit' => ['required', 'boolean'],
        ]);

        $accountUserAccess->update([
            'can_edit' => $validated['can_edit'],
        ]);

        return $this->success('Account access updated successfully', $accountUserAccess);
    }

    public function destroy(Account $account, AccountUserAccess $accountUserAccess)
    {
        if ($account->created_by_user_id !== Auth::id()) {
            return $this->error('You do not have permission to remove access.');
        }

        if ($accountUserAccess->account_id !== $account->id) {
            return $this->error('Access record not found.');
        }

        if (!$accountUserAccess->delete()) {
            return $this->error('Account access could not be deleted');
        }

        return $this->success('Account access removed successfully');
    }
}