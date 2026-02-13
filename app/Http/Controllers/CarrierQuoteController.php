<?php

namespace App\Http\Controllers;

use App\Models\Deal;
use App\Models\CarrierQuote;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CarrierQuoteController extends Controller
{
    /**
     * List carrier quotes for a deal
     */
    public function index(Deal $deal)
    {
        $this->authorize('view', $deal);

        $quotes = $deal->carrierQuotes()
            ->latest()
            ->get();

        return $this->success('Carrier quotes retrieved successfully', $quotes);
    }

    /**
     * Store a new carrier quote
     */
    public function store(Request $request, Deal $deal)
    {
        $this->authorize('update', $deal);

        $data = $request->validate([
            'carrier_name' => ['nullable', 'string', 'max:255'],
            'carrier_mc' => ['nullable', 'string', 'max:20'],
            'carrier_usdot' => ['nullable', 'string', 'max:20'],

            'contact_name' => ['nullable', 'string', 'max:255'],
            'contact_phone' => ['nullable', 'string', 'max:30'],
            'contact_email' => ['nullable', 'email', 'max:255'],

            'carrier_rate' => ['nullable', 'numeric', 'min:0', 'max:99999999.99'],
            'note' => ['nullable', 'string', 'max:255'],
        ]);

        $quote = $deal->carrierQuotes()->create([
            ...$data,
            'created_by_user_id' => Auth::id(),
        ]);

        if (! $quote) {
            return $this->error('Failed to create carrier quote', [], 500);
        }

        return $this->success('Carrier quote created successfully', $quote, 201);
    }

    /**
     * Show a specific carrier quote
     */
    public function show(Deal $deal, CarrierQuote $carrierQuote)
    {
        $this->authorize('view', $carrierQuote);

        if ($carrierQuote->deal_id !== $deal->id) {
            return $this->error('Quote does not belong to this deal', [], 404);
        }

        return $this->success('Carrier quote retrieved successfully', $carrierQuote);
    }

    /**
     * Update a carrier quote
     */
    public function update(Request $request, Deal $deal, CarrierQuote $carrierQuote)
    {
        $this->authorize('update', $carrierQuote);

        if ($carrierQuote->deal_id !== $deal->id) {
            return $this->error('Quote does not belong to this deal', [], 404);
        }

        $data = $request->validate([
            'carrier_name' => ['nullable', 'string', 'max:255'],
            'carrier_mc' => ['nullable', 'string', 'max:20'],
            'carrier_usdot' => ['nullable', 'string', 'max:20'],

            'contact_name' => ['nullable', 'string', 'max:255'],
            'contact_phone' => ['nullable', 'string', 'max:30'],
            'contact_email' => ['nullable', 'email', 'max:255'],

            'carrier_rate' => ['nullable', 'numeric', 'min:0', 'max:99999999.99'],
            'note' => ['nullable', 'string', 'max:255'],
        ]);

        $carrierQuote->update($data);

        if (! $carrierQuote->wasChanged()) {
            return $this->success('No changes detected', $carrierQuote);
        }

        return $this->success('Carrier quote updated successfully', $carrierQuote);
    }

    /**
     * Delete carrier quote
     */
    public function destroy(Deal $deal, CarrierQuote $carrierQuote)
    {
        $this->authorize('delete', $carrierQuote);

        if ($carrierQuote->deal_id !== $deal->id) {
            return $this->error('Quote does not belong to this deal', [], 404);
        }

        $deleted = $carrierQuote->delete();

        if (! $deleted) {
            return $this->error('Carrier quote could not be deleted');
        }

        return $this->success('Carrier quote deleted successfully');
    }
}