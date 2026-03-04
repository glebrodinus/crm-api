<?php

namespace App\Http\Controllers;

use App\Models\Deal;
use App\Models\DealQuote;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class DealQuoteController extends Controller
{
    public function index(Deal $deal)
    {
        $this->authorize('view', $deal);

        $quotes = $deal->quotes()->latest()->get();

        return $this->success('Deal quotes retrieved successfully', $quotes);
    }

    public function store(Request $request, Deal $deal)
    {
        $this->authorize('update', $deal);

        $data = $request->validate([
            'status' => ['nullable', 'in:draft,sent,rejected'],

            'customer_rate' => ['required', 'numeric', 'min:0', 'max:99999999.99'],
            'fuel_surcharge' => ['nullable', 'numeric', 'min:0', 'max:99999999.99'],
            'accessorials' => ['nullable', 'array'],
            'note' => ['nullable', 'string', 'max:255'],

            'sent_at' => ['nullable', 'date'],
            'expires_at' => ['nullable', 'date', 'after_or_equal:sent_at'],
        ]);

        $status = $data['status'] ?? 'draft';

        // convenience: if sent, ensure sent_at
        if ($status === 'sent' && empty($data['sent_at'])) {
            $data['sent_at'] = now();
        }

        $quote = $deal->quotes()->create([
            ...$data,
            'created_by_user_id' => Auth::id(),
            'status' => $status,
        ]);

        if (! $quote) {
            return $this->error('Failed to create deal quote', [], 500);
        }

        return $this->success('Deal quote created successfully', $quote, 201);
    }

    public function show(Deal $deal, DealQuote $quote)
    {
        $this->authorize('view', $deal);

        if ($quote->deal_id !== $deal->id) {
            return $this->error('Quote does not belong to this deal', [], 404);
        }

        return $this->success('Deal quote retrieved successfully', $quote);
    }

    public function update(Request $request, Deal $deal, DealQuote $quote)
    {
        $this->authorize('update', $deal);

        if ($quote->deal_id !== $deal->id) {
            return $this->error('Quote does not belong to this deal', [], 404);
        }

        // If quote is not draft, lock edits (your rule)
        if ($quote->status !== 'draft') {
            return $this->error('Only draft quotes can be edited.', [], 422);
        }

        $data = $request->validate([
            'status' => ['required', 'in:draft,sent,rejected'],

            'customer_rate' => ['required', 'numeric', 'min:0', 'max:99999999.99'],
            'fuel_surcharge' => ['nullable', 'numeric', 'min:0', 'max:99999999.99'],
            'accessorials' => ['nullable', 'array'],
            'note' => ['nullable', 'string', 'max:255'],

            'sent_at' => ['nullable', 'date'],
            'expires_at' => ['nullable', 'date', 'after_or_equal:sent_at'],

            // selecting (accepting) quote happens via selected_at
            'select' => ['nullable', 'boolean'],
            'customer_accepted_method' => ['nullable', 'string', 'max:20'], // email/sms/verbal
        ]);

        $status = $data['status'];

        if ($status === 'sent' && empty($data['sent_at'])) {
            $data['sent_at'] = now();
        }

        // Remove non-column fields before update
        $select = (bool)($data['select'] ?? false);
        $acceptedMethod = $data['customer_accepted_method'] ?? null;
        unset($data['select'], $data['customer_accepted_method']);

        $quote->update($data);

        // If user selected this quote as accepted:
        if ($select) {
            // mark all other quotes unselected
            DealQuote::where('deal_id', $deal->id)->update(['selected_at' => null]);

            // select this one
            $quote->update(['selected_at' => now()]);

            // snapshot to deal
            $deal->update([
                'customer_rate' => $quote->customer_rate,
                'customer_accepted_at' => now(),
                'customer_accepted_by_user_id' => Auth::id(),
                'customer_accepted_method' => $acceptedMethod,
                // optional: move to booked or keep quoted until carrier booked
                // 'status' => 'booked',
            ]);
        }

        return $this->success('Deal quote updated successfully', $quote->fresh());
    }

    public function destroy(Deal $deal, DealQuote $quote)
    {
        $this->authorize('update', $deal);

        if ($quote->deal_id !== $deal->id) {
            return $this->error('Quote does not belong to this deal', [], 404);
        }

        // optional: prevent deleting sent quotes
        // if ($quote->status !== 'draft') return $this->error('Only draft quotes can be deleted.', [], 422);

        $deleted = $quote->delete();

        if (! $deleted) {
            return $this->error('Deal quote could not be deleted');
        }

        return $this->success('Deal quote deleted successfully');
    }
}