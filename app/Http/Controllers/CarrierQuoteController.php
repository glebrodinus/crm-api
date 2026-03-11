<?php

namespace App\Http\Controllers;

use App\Models\Deal;
use App\Models\CarrierQuote;
use Illuminate\Http\Request;

class CarrierQuoteController extends Controller
{
    public function index(Deal $deal)
    {
        $this->authorize('view', $deal);

        $quotes = $deal->carrierQuotes()
            ->latest()
            ->get();

        return $this->success('Carrier quotes retrieved successfully', $quotes);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'deal_id' => ['required', 'exists:deals,id'],

            'carrier_name' => ['nullable', 'string', 'max:255'],
            'carrier_mc' => ['nullable', 'string', 'max:20'],
            'carrier_usdot' => ['nullable', 'string', 'max:20'],

            'contact_name' => ['nullable', 'string', 'max:255'],
            'contact_phone' => ['nullable', 'string', 'max:30'],
            'contact_email' => ['nullable', 'email', 'max:255'],

            'carrier_rate' => ['required', 'numeric', 'min:0', 'max:99999999.99'],
            'note' => ['nullable', 'string', 'max:255'],
        ]);

        $deal = Deal::findOrFail($data['deal_id']);

        $this->authorize('update', $deal);

        $quote = CarrierQuote::create($data);

        return $this->success('Carrier quote created successfully', $quote, 201);
    }

    public function show(CarrierQuote $carrierQuote)
    {
        $deal = Deal::findOrFail($carrierQuote->deal_id);

        $this->authorize('view', $deal);

        return $this->success('Carrier quote retrieved successfully', $carrierQuote);
    }

    public function update(Request $request, CarrierQuote $carrierQuote)
    {
        $deal = Deal::findOrFail($carrierQuote->deal_id);

        $this->authorize('update', $deal);

        $data = $request->validate([
            'carrier_name' => ['nullable', 'string', 'max:255'],
            'carrier_mc' => ['nullable', 'string', 'max:20'],
            'carrier_usdot' => ['nullable', 'string', 'max:20'],

            'contact_name' => ['nullable', 'string', 'max:255'],
            'contact_phone' => ['nullable', 'string', 'max:30'],
            'contact_email' => ['nullable', 'email', 'max:255'],

            'carrier_rate' => ['required', 'numeric', 'min:0', 'max:99999999.99'],
            'note' => ['nullable', 'string', 'max:255'],
        ]);

        $carrierQuote->update($data);

        if (! $carrierQuote->wasChanged()) {
            return $this->success('No changes detected', $carrierQuote);
        }

        return $this->success('Carrier quote updated successfully', $carrierQuote);
    }

    public function destroy(CarrierQuote $carrierQuote)
    {
        $deal = Deal::findOrFail($carrierQuote->deal_id);

        $this->authorize('update', $deal);

        $deleted = $carrierQuote->delete();

        if (! $deleted) {
            return $this->error('Carrier quote could not be deleted');
        }

        return $this->success('Carrier quote deleted successfully');
    }
}