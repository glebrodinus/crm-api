<?php

namespace App\Http\Controllers;

use App\Models\Deal;
use App\Models\Account;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class DealController extends Controller
{
    /**
     * Display a listing of the resource.
     * (Optional for later – you can implement list filters by pipeline/status)
     */
public function index()
{
    $this->authorize('viewAny', Deal::class);

    $deals = Deal::query()
        ->where('owner_user_id', Auth::id())
        ->latest()
        ->get();

    return $this->success('Deals retrieved successfully', $deals);
}

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $data = $request->validate([
            'account_id' => ['required', 'exists:accounts,id'],
            'contact_id' => ['nullable', 'exists:contacts,id'],

            'pipeline_type' => ['required', 'in:cold,repeat'],
            'status' => ['nullable', 'in:quote_requested,quote_sent,negotiating,booked,completed,cancelled,lost'],

            'origin_city' => ['nullable', 'string', 'max:255'],
            'origin_state' => ['nullable', 'string', 'size:2'],
            'origin_zip' => ['nullable', 'string', 'max:10'],

            'destination_city' => ['nullable', 'string', 'max:255'],
            'destination_state' => ['nullable', 'string', 'size:2'],
            'destination_zip' => ['nullable', 'string', 'max:10'],

            'equipment_type' => ['nullable', 'string', 'max:255'],
            'commodity' => ['nullable', 'string', 'max:255'],
            'weight_lbs' => ['nullable', 'integer', 'min:0'],

            'note' => ['nullable', 'string', 'max:255'],
            'closed_at' => ['nullable', 'date'],
        ]);

        $account = Account::findOrFail($data['account_id']);

        // same pattern as your ActivityController
        $this->authorize('update', $account);

        $deal = Deal::create([
            ...$data,
            'owner_user_id' => Auth::id(),
            'created_by_user_id' => Auth::id(),
            'status' => $data['status'] ?? 'quote_requested',
        ]);

        if (! $deal) {
            return $this->error('Failed to create deal', [], 500);
        }

        return $this->success('Deal created successfully', $deal, 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(Deal $deal)
    {
        $this->authorize('view', $deal);

        // You probably want quotes too
        $deal->load(['account', 'contact', 'quotes', 'tasks', 'activities', 'notes']);

        return $this->success('Deal retrieved successfully', $deal);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Deal $deal)
    {
        $this->authorize('update', $deal);

        $data = $request->validate([
            'contact_id' => ['nullable', 'exists:contacts,id'],

            'pipeline_type' => ['required', 'in:cold,repeat'],
            'status' => ['required', 'in:quote_requested,quote_sent,negotiating,booked,completed,cancelled,lost'],

            'origin_city' => ['nullable', 'string', 'max:255'],
            'origin_state' => ['nullable', 'string', 'size:2'],
            'origin_zip' => ['nullable', 'string', 'max:10'],

            'destination_city' => ['nullable', 'string', 'max:255'],
            'destination_state' => ['nullable', 'string', 'size:2'],
            'destination_zip' => ['nullable', 'string', 'max:10'],

            'equipment_type' => ['nullable', 'string', 'max:255'],
            'commodity' => ['nullable', 'string', 'max:255'],
            'weight_lbs' => ['nullable', 'integer', 'min:0'],

            'note' => ['nullable', 'string', 'max:255'],
            'closed_at' => ['nullable', 'date'],
        ]);

        // simple rule: if status becomes a "closed" status, set closed_at if not provided
        $closedStatuses = ['completed', 'cancelled', 'lost'];
        if (in_array($data['status'], $closedStatuses, true) && empty($data['closed_at'])) {
            $data['closed_at'] = now();
        }

        // if status re-opens, clear closed_at (optional)
        $openStatuses = ['quote_requested', 'quote_sent', 'negotiating', 'booked'];
        if (in_array($data['status'], $openStatuses, true)) {
            $data['closed_at'] = null;
        }

        $deal->update($data);

        if (! $deal->wasChanged()) {
            return $this->success('No changes detected', $deal);
        }

        return $this->success('Deal updated successfully', $deal);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Deal $deal)
    {
        $this->authorize('delete', $deal);

        $deleted = $deal->delete();

        if (! $deleted) {
            return $this->error('Deal could not be deleted');
        }

        return $this->success('Deal deleted successfully');
    }
}