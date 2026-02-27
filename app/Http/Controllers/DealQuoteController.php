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
            'status' => ['nullable', 'in:draft,sent,accepted,rejected,expired'],

            'customer_rate' => ['nullable', 'numeric', 'min:0', 'max:99999999.99'],
            'accessorials' => ['nullable', 'array'],
            'note' => ['nullable', 'string', 'max:255'],

            'sent_at' => ['nullable', 'date'],
            'expires_at' => ['nullable', 'date', 'after_or_equal:sent_at'],
        ]);

        $quote = $deal->quotes()->create([
            ...$data,
            'created_by_user_id' => Auth::id(),
            'status' => $data['status'] ?? 'draft',
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

        $data = $request->validate([
            'status' => ['required', 'in:draft,sent,accepted,rejected,expired'],

            'customer_rate' => ['nullable', 'numeric', 'min:0', 'max:99999999.99'],
            'accessorials' => ['nullable', 'array'],
            'note' => ['nullable', 'string', 'max:255'],

            'sent_at' => ['nullable', 'date'],
            'expires_at' => ['nullable', 'date', 'after_or_equal:sent_at'],
        ]);

        // small convenience: if status becomes "sent" and sent_at isn't provided, set now
        if ($data['status'] === 'sent' && empty($data['sent_at'])) {
            $data['sent_at'] = now();
        }

        $quote->update($data);

        if (! $quote->wasChanged()) {
            return $this->success('No changes detected', $quote);
        }

        return $this->success('Deal quote updated successfully', $quote);
    }

    public function destroy(Deal $deal, DealQuote $quote)
    {
        $this->authorize('update', $deal);

        if ($quote->deal_id !== $deal->id) {
            return $this->error('Quote does not belong to this deal', [], 404);
        }

        $deleted = $quote->delete();

        if (! $deleted) {
            return $this->error('Deal quote could not be deleted');
        }

        return $this->success('Deal quote deleted successfully');
    }
}