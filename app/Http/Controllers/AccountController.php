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

    public function checkSimilar(Request $request)
    {
        $request->validate([
            'name' => ['required', 'string', 'max:255'],
        ]);

        $accounts = Account::query()
            ->where('created_by_user_id', Auth::id())
            ->where(function ($query) use ($request) {
                $query->where('name', 'like', '%' . $request->name . '%')
                    ->orWhere('dba_name', 'like', '%' . $request->name . '%');
            })
            ->limit(10)
            ->get(['name', 'dba_name', 'city', 'state']);

        return $accounts->isEmpty()
            ? $this->success('No similar accounts found', [])
            : $this->success('Similar accounts retrieved', $accounts);
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
            'timezone'  => ['nullable', 'in:PST,MST,CST,EST'],
            'note'      => ['nullable', 'string', 'max:1000'],

            // Account-level follow-up
            'follow_up_at'   => ['nullable', 'date'],
            'follow_up_type' => ['nullable', 'in:call,email,text,meeting'],
            'follow_up_note' => ['nullable', 'string', 'max:255'],

            // Optional initial contact
            'contact_first_name' => ['nullable', 'string', 'max:50'],
            'contact_last_name'  => ['nullable', 'string', 'max:50'],
            'contact_phone'      => ['nullable', 'string', 'max:20'],
            'contact_email'      => ['nullable', 'email', 'max:255'],

            // Optional initial link
            'url_label' => ['nullable', 'string', 'max:255'],
            'url'       => ['nullable', 'string', 'max:2048'],
        ]);

        $accountData = collect($data)->except([
            'contact_first_name',
            'contact_last_name',
            'contact_phone',
            'contact_email',
            'url_label',
            'url',
        ])->toArray();

        $account = Account::create($accountData);

        if (! $account) {
            return $this->error('Failed to create account', [], 500);
        }

        // Optional: create first contact
        if (! empty($data['contact_first_name'])) {
            $contact = $account->contacts()->create([
                'first_name' => $data['contact_first_name'],
                'last_name'  => $data['contact_last_name'] ?? null,
                'phone'      => $data['contact_phone'] ?? null,
                'email'      => $data['contact_email'] ?? null,
            ]);

            // Optional: if follow-up exists and no explicit contact id is provided on create,
            // auto-attach the newly created contact
            if (! empty($data['follow_up_at']) && empty($account->follow_up_contact_id)) {
                $account->follow_up_contact_id = $contact->id;
                $account->save();
            }
        }

        // Optional: create first link
        if (! empty($data['url'])) {
            $account->links()->create([
                'type'      => 'link',
                'url_label' => $data['url_label'] ?? null,
                'url'       => $data['url'],
            ]);
        }

        $account->load(['contacts', 'links', 'followUpContact']);
        $account->refresh();

        return $this->success('Account created successfully', $account, 201);
    }

    public function show(Account $account)
    {
        $this->authorize('view', $account);

        $account->load([
            'followUpContact',
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
            'timezone'  => ['nullable', 'in:PST,MST,CST,EST'],
            'note'      => ['nullable', 'string', 'max:1000'],

            'follow_up_at'         => ['nullable', 'date'],
            'follow_up_type'       => ['nullable', 'in:call,email,text,meeting'],
            'follow_up_contact_id' => ['nullable', 'integer', 'exists:contacts,id'],
            'follow_up_note'       => ['nullable', 'string', 'max:255'],
        ]);

        unset(
            $data['created_by_user_id'],
            $data['updated_by_user_id'],
            $data['last_contacted_at'],
            $data['last_attempted_at'],
            $data['last_deal_at'],
            $data['qualified_at'],
            $data['qualified_by_user_id'],
            $data['qualified_reason'],
            $data['disqualified_at'],
            $data['disqualified_by_user_id'],
            $data['disqualified_reason'],
            $data['unreachable_at'],
            $data['unreachable_by_user_id'],
            $data['unreachable_reason']
        );

        $account->fill($data);

        if (! $account->isDirty()) {
            return $this->success('No changes', $account);
        }

        $account->save();
        $account->refresh();

        return $this->success('Account updated successfully', $account);
    }

    public function markReachable(Account $account)
    {
        $this->authorize('markReachable', $account);

        $account->unreachable_at = null;
        $account->unreachable_reason = null;
        $account->unreachable_by_user_id = null;
        $account->save();

        return $this->success('Account marked as reachable', $account);
    }

    public function markUnreachable(Request $request, Account $account)
    {
        $this->authorize('markUnreachable', $account);

        $data = $request->validate([
            'reason' => ['nullable', 'string', 'max:255'],
        ]);

        $account->unreachable_at = now();
        $account->unreachable_reason = $data['reason'] ?? null;
        $account->unreachable_by_user_id = Auth::id();
        $account->save();

        return $this->success('Account marked as unreachable', $account);
    }

    public function qualify(Request $request, Account $account)
    {
        $this->authorize('qualify', $account);

        $data = $request->validate([
            'reason' => ['nullable', 'string', 'max:255'],
        ]);

        if ($account->qualified_at) {
            return $this->error('Account is already qualified', [], 400);
        }

        $account->qualified_at = now();
        $account->qualified_by_user_id = Auth::id();
        $account->qualified_reason = $data['reason'] ?? null;
        $account->save();

        return $this->success('Account qualified successfully', $account);
    }

    public function disqualify(Request $request, Account $account)
    {
        $this->authorize('disqualify', $account);

        $data = $request->validate([
            'reason' => ['nullable', 'string', 'max:255'],
        ]);

        if ($account->disqualified_at) {
            return $this->error('Account is already disqualified', [], 400);
        }

        $account->disqualified_at = now();
        $account->disqualified_by_user_id = Auth::id();
        $account->disqualified_reason = $data['reason'] ?? null;
        $account->save();

        return $this->success('Account disqualified successfully', $account);
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