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
            'name' => 'required|string|max:50',
        ]);

        $account = Account::create($data);

        if (! $account) {
            return $this->error('Failed to create account', 500);
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

        return $this->success('Account retrieved successfully', $account);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }
}
