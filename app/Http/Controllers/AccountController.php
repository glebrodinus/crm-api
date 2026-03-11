<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Account;
use Illuminate\Support\Facades\Auth;
use App\Helpers\DealHelper;

class AccountController extends Controller
{
    public function index()
    {
        $this->authorize('viewAny', Account::class);

        $accounts = Account::query()
            ->where('created_by_user_id', Auth::id())
            ->latest()
            ->get();

        $message = $accounts->isEmpty()
            ? 'No accounts found'
            : 'Accounts retrieved successfully';

        return $this->success($message, $accounts);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name'      => ['required', 'string', 'max:255'],
            'dba_name'  => ['nullable', 'string', 'max:255'],
            'website'   => ['nullable', 'string', 'max:255'],
            'email'     => ['nullable', 'email', 'max:255'],
            'city'      => ['nullable', 'string', 'max:100'],
            'state'     => ['nullable', 'string', 'size:2'],
            'zip'       => ['nullable', 'string', 'max:20'],
            'status'    => ['nullable', 'in:lead,active,inactive'],
            'address'   => ['nullable', 'string', 'max:255'],
            'address_2' => ['nullable', 'string', 'max:255'],
            'country'   => ['nullable', 'string', 'max:3'],
            'phone'     => ['nullable', 'string', 'max:20'],

            // unreachable flag (optional set on create)
            'is_unreachable' => ['nullable', 'boolean'],
            'unreachable_reason' => ['nullable', 'string', 'max:255'],

            // Optional initial contact
            'contact_first_name' => ['nullable', 'string', 'max:50'],
            'contact_last_name'  => ['nullable', 'string', 'max:50'],
            'contact_phone'      => ['nullable', 'string', 'max:20'],
            'contact_email'      => ['nullable', 'email', 'max:255'],

            // Optional initial task
            'task_type'   => ['nullable', 'string', 'max:50'],
            'task_due_at' => ['nullable', 'date'],
        ]);

        // server-controlled fields
        $data['created_by_user_id'] = Auth::id();

        // if unreachable set on create, stamp it
        if (!empty($data['is_unreachable'])) {
            $data['unreachable_at'] = now();
        }

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
        }

        // Optional: create first task
        if (! empty($data['task_type'])) {
            $account->tasks()->create([
                'type' => $data['task_type'],
                'due_at' => $data['task_due_at'] ?? null,
            ]);
        }

        $account->load(['contacts', 'tasks']);
        $account->refresh();

        return $this->success('Account created successfully', $account, 201);
    }

    public function show(Account $account)
    {
        $this->authorize('view', $account);

        $account->load([
            'contacts',
            'deals.trailerTypes:id,deal_id,type',
            'deals.stops',
            'deals.marketRates',
            'deals.quotes',
            'deals.carrierQuotes',
            'activities',
            'tasks',
            'notes',
            'links',
        ]);

        DealHelper::transformTrailerTypes($account->deals);

        return $this->success('Account retrieved successfully', $account);
    }

    public function update(Request $request, Account $account)
    {
        $this->authorize('update', $account);

        $data = $request->validate([
            'name'      => ['sometimes', 'required', 'string', 'max:255'],
            'dba_name'  => ['nullable', 'string', 'max:255'],
            'website'   => ['nullable', 'string', 'max:255'],
            'email'     => ['nullable', 'email', 'max:255'],
            'city'      => ['nullable', 'string', 'max:100'],
            'state'     => ['nullable', 'string', 'size:2'],
            'zip'       => ['nullable', 'string', 'max:20'],
            'status'    => ['nullable', 'in:lead,active,inactive'],
            'address'   => ['nullable', 'string', 'max:255'],
            'address_2' => ['nullable', 'string', 'max:255'],
            'country'   => ['nullable', 'string', 'max:3'],
            'phone'     => ['nullable', 'string', 'max:20'],

            // unreachable toggle
            'is_unreachable' => ['nullable', 'boolean'],
            'unreachable_reason' => ['nullable', 'string', 'max:255'],

            // optional: allow clearing reason
            // 'unreachable_reason' => ['nullable', 'string', 'max:255'],
        ]);

        // Never allow system-controlled fields to be updated here
        unset(
            $data['created_by_user_id'],
            $data['last_contacted_at'],
            $data['last_attempted_at'],
            $data['last_deal_at'],
            $data['qualified_at'],
            $data['qualified_by_user_id'],
            $data['disqualified_at'],
            $data['disqualified_by_user_id']
        );

        // handle unreachable stamps (only if field provided)
        if (array_key_exists('is_unreachable', $data)) {
            $isUnreachable = (bool) $data['is_unreachable'];

            if ($isUnreachable && !$account->is_unreachable) {
                $data['unreachable_at'] = now();
            }

            if (!$isUnreachable) {
                $data['unreachable_at'] = null;
                $data['unreachable_reason'] = null;
            }
        }

        $account->fill($data);

        if (! $account->isDirty()) {
            return $this->success('No changes', $account);
        }

        $account->save();
        $account->refresh();

        return $this->success('Account updated successfully', $account);
    }

    public function destroy(Account $account)
    {
        $this->authorize('delete', $account);

        $deleted = $account->delete();

        if (! $deleted) {
            return $this->error('Failed to delete account', [], 500);
        }

        return $this->success('Account deleted successfully');
    }
}