<?php

namespace App\Http\Controllers;

use App\Models\Deal;
use App\Models\DealQuote;
use Illuminate\Http\Request;

class DealQuoteController extends Controller
{
    public function index(Deal $deal)
    {
        $this->authorize('view', $deal);

        $quotes = $deal->quotes()
            ->latest()
            ->get();

        return $this->success('Deal quotes retrieved successfully', $quotes);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'deal_id' => ['required', 'exists:deals,id'],

            'status' => ['nullable', 'in:draft,sent,rejected'],

            'customer_rate' => ['required', 'numeric', 'min:0', 'max:99999999.99'],
            'competitor_rate' => ['nullable', 'numeric', 'min:0', 'max:99999999.99'],
            'customer_counter_rate' => ['nullable', 'numeric', 'min:0', 'max:99999999.99'],

            'note' => ['nullable', 'string', 'max:255'],
            'rejected_reason' => ['nullable', 'string', 'max:255'],

            'sent_at' => ['nullable', 'date'],
            'expires_at' => ['nullable', 'date', 'after_or_equal:sent_at'],
            'rejected_at' => ['nullable', 'date'],
        ]);

        $deal = Deal::findOrFail($data['deal_id']);

        $this->authorize('update', $deal);

        $status = $data['status'] ?? 'draft';

        if ($status === 'sent' && empty($data['sent_at'])) {
            $data['sent_at'] = now();
        }

        if ($status === 'rejected' && empty($data['rejected_at'])) {
            $data['rejected_at'] = now();
        }

        $quote = DealQuote::create([
            ...$data,
            'status' => $status,
        ]);

        return $this->success('Deal quote created successfully', $quote, 201);
    }

    public function show(DealQuote $dealQuote)
    {
        $deal = Deal::findOrFail($dealQuote->deal_id);

        $this->authorize('view', $deal);

        return $this->success('Deal quote retrieved successfully', $dealQuote);
    }

    public function update(Request $request, DealQuote $dealQuote)
    {
        $deal = Deal::findOrFail($dealQuote->deal_id);

        $this->authorize('update', $deal);

        if ($dealQuote->selected_at) {
            return $this->error('Selected quote cannot be edited.', [], 422);
        }

        $data = $request->validate([
            'status' => ['required', 'in:draft,sent,rejected'],

            'customer_rate' => ['required', 'numeric', 'min:0', 'max:99999999.99'],
            'competitor_rate' => ['nullable', 'numeric', 'min:0', 'max:99999999.99'],
            'customer_counter_rate' => ['nullable', 'numeric', 'min:0', 'max:99999999.99'],

            'note' => ['nullable', 'string', 'max:255'],
            'rejected_reason' => ['nullable', 'string', 'max:255'],

            'sent_at' => ['nullable', 'date'],
            'expires_at' => ['nullable', 'date', 'after_or_equal:sent_at'],
            'rejected_at' => ['nullable', 'date'],

            'select' => ['nullable', 'boolean'],
            'customer_accepted_method' => ['nullable', 'string', 'max:20'],
        ]);

        $status = $data['status'];

        if ($status === 'sent' && empty($data['sent_at'])) {
            $data['sent_at'] = $dealQuote->sent_at ?? now();
        }

        if ($status === 'rejected' && empty($data['rejected_at'])) {
            $data['rejected_at'] = $dealQuote->rejected_at ?? now();
        }

        if ($status !== 'rejected') {
            $data['rejected_at'] = null;
            $data['rejected_reason'] = null;
        }

        $select = (bool) ($data['select'] ?? false);
        $acceptedMethod = $data['customer_accepted_method'] ?? null;

        unset($data['select'], $data['customer_accepted_method']);

        $dealQuote->update($data);

        if ($select) {
            DealQuote::where('deal_id', $deal->id)
                ->update(['selected_at' => null]);

            $dealQuote->update([
                'selected_at' => now(),
            ]);

            $deal->update([
                'customer_rate' => $dealQuote->customer_rate,
                'customer_accepted_at' => now(),
                'customer_accepted_method' => $acceptedMethod,
                'status' => 'booked',
            ]);
        }

        return $this->success('Deal quote updated successfully', $dealQuote->fresh());
    }

    public function destroy(DealQuote $dealQuote)
    {
        $deal = Deal::findOrFail($dealQuote->deal_id);

        $this->authorize('update', $deal);

        if ($dealQuote->selected_at) {
            return $this->error('Selected quote cannot be deleted.', [], 422);
        }

        $deleted = $dealQuote->delete();

        if (! $deleted) {
            return $this->error('Deal quote could not be deleted');
        }

        return $this->success('Deal quote deleted successfully');
    }
}