<?php

namespace App\Http\Controllers;

use App\Models\Deal;
use App\Models\DealStop;
use App\Models\Account;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class DealController extends Controller
{
    public function index()
    {
        $this->authorize('viewAny', Deal::class);

        $deals = Deal::query()
            ->where('owner_user_id', Auth::id())
            ->latest()
            ->get();

        return $this->success('Deals retrieved successfully', $deals);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'account_id' => ['required', 'exists:accounts,id'],
            'contact_id' => ['nullable', 'exists:contacts,id'],

            'status' => ['nullable', 'in:requested,quoted,booked,lost'],

            'origin_city' => ['nullable', 'string', 'max:255'],
            'origin_state' => ['nullable', 'string', 'size:2'],
            'origin_zip' => ['nullable', 'string', 'max:10'],

            'destination_city' => ['nullable', 'string', 'max:255'],
            'destination_state' => ['nullable', 'string', 'size:2'],
            'destination_zip' => ['nullable', 'string', 'max:10'],

            'commodity' => ['nullable', 'string', 'max:255'],
            'weight_lbs' => ['nullable', 'integer', 'min:0'],

            'pickup_date' => ['nullable', 'date'],
            'delivery_date' => ['nullable', 'date'],

            'trailer_types' => ['nullable', 'array'],
            'trailer_types.*' => ['in:RGN,F,SD,HS,R,V,CN'],

            'is_oversize' => ['nullable', 'boolean'],
            'is_overweight' => ['nullable', 'boolean'],
            'tarp_required' => ['nullable', 'boolean'],
            'is_team' => ['nullable', 'boolean'],
            'is_government' => ['nullable', 'boolean'],
            'is_non_operational' => ['nullable', 'boolean'],

            'is_temp_required' => ['nullable', 'boolean'],
            'temperature_from' => ['nullable', 'integer', 'min:-100', 'max:150'],
            'temperature_to' => ['nullable', 'integer', 'min:-100', 'max:150'],

            'customer_rate' => ['nullable', 'numeric', 'min:0'],
            'carrier_rate' => ['nullable', 'numeric', 'min:0'],
            'lost_rate' => ['nullable', 'numeric', 'min:0'],

            'company_profit' => ['nullable', 'numeric'],
            'agent_profit' => ['nullable', 'numeric'],
            'agent_commission_percent' => ['nullable', 'numeric', 'min:0', 'max:100'],

            'note' => ['nullable', 'string', 'max:255'],
            'closed_at' => ['nullable', 'date'],

            // stops embedded in deal
            'stops' => ['nullable', 'array', 'min:2', 'max:10'],
            'stops.*.type' => ['required_with:stops', 'in:pick,drop,stop'],
            'stops.*.city' => ['nullable', 'string', 'max:255'],
            'stops.*.state' => ['nullable', 'string', 'size:2'],
            'stops.*.zip' => ['nullable', 'string', 'max:10'],
            'stops.*.date' => ['nullable', 'date'],
            'stops.*.note' => ['nullable', 'string', 'max:255'],
        ]);

        $account = Account::findOrFail($data['account_id']);
        $this->authorize('update', $account);

        $status = $data['status'] ?? 'requested';

        // closed_at logic: only lost is "closed" for now
        if ($status === 'lost' && empty($data['closed_at'])) {
            $data['closed_at'] = now();
        }
        if ($status !== 'lost') {
            $data['closed_at'] = null;
        }

        $stops = $data['stops'] ?? null;
        unset($data['stops']); // not a column

        $deal = Deal::create([
            ...$data,
            'owner_user_id' => Auth::id(),
            'created_by_user_id' => Auth::id(),
            'status' => $status,
        ]);

        if (! $deal) {
            return $this->error('Failed to create deal', [], 500);
        }

        if (is_array($stops)) {
            $this->syncStops($deal, $stops);
        }

        $deal->load(['stops']);

        return $this->success('Deal created successfully', $deal, 201);
    }

    public function show(Deal $deal)
    {
        $this->authorize('view', $deal);

        $deal->load([
            'account',
            'contact',
            'stops',
            'quotes',
            'carrierQuotes',
            'tasks',
            'activities',
            'notes',
        ]);

        return $this->success('Deal retrieved successfully', $deal);
    }

    public function update(Request $request, Deal $deal)
    {
        $this->authorize('update', $deal);

        $data = $request->validate([
            'contact_id' => ['nullable', 'exists:contacts,id'],

            'status' => ['required', 'in:requested,quoted,booked,lost'],

            'origin_city' => ['nullable', 'string', 'max:255'],
            'origin_state' => ['nullable', 'string', 'size:2'],
            'origin_zip' => ['nullable', 'string', 'max:10'],

            'destination_city' => ['nullable', 'string', 'max:255'],
            'destination_state' => ['nullable', 'string', 'size:2'],
            'destination_zip' => ['nullable', 'string', 'max:10'],

            'commodity' => ['nullable', 'string', 'max:255'],
            'weight_lbs' => ['nullable', 'integer', 'min:0'],

            'pickup_date' => ['nullable', 'date'],
            'delivery_date' => ['nullable', 'date'],

            'trailer_types' => ['nullable', 'array'],
            'trailer_types.*' => ['in:RGN,F,SD,HS,R,V,CN'],

            'is_oversize' => ['nullable', 'boolean'],
            'is_overweight' => ['nullable', 'boolean'],
            'tarp_required' => ['nullable', 'boolean'],
            'is_team' => ['nullable', 'boolean'],
            'is_government' => ['nullable', 'boolean'],
            'is_non_operational' => ['nullable', 'boolean'],

            'is_temp_required' => ['nullable', 'boolean'],
            'temperature_from' => ['nullable', 'integer', 'min:-100', 'max:150'],
            'temperature_to' => ['nullable', 'integer', 'min:-100', 'max:150'],

            'customer_rate' => ['nullable', 'numeric', 'min:0'],
            'carrier_rate' => ['nullable', 'numeric', 'min:0'],
            'lost_rate' => ['nullable', 'numeric', 'min:0'],

            'company_profit' => ['nullable', 'numeric'],
            'agent_profit' => ['nullable', 'numeric'],
            'agent_commission_percent' => ['nullable', 'numeric', 'min:0', 'max:100'],

            'note' => ['nullable', 'string', 'max:255'],
            'closed_at' => ['nullable', 'date'],

            // stops embedded
            'stops' => ['sometimes', 'array', 'min:2', 'max:10'],
            'stops.*.type' => ['required_with:stops', 'in:pick,drop,stop'],
            'stops.*.city' => ['nullable', 'string', 'max:255'],
            'stops.*.state' => ['nullable', 'string', 'size:2'],
            'stops.*.zip' => ['nullable', 'string', 'max:10'],
            'stops.*.date' => ['nullable', 'date'],
            'stops.*.note' => ['nullable', 'string', 'max:255'],
        ]);

        // closed_at logic: only lost is closed
        if ($data['status'] === 'lost' && empty($data['closed_at'])) {
            $data['closed_at'] = now();
        }
        if ($data['status'] !== 'lost') {
            $data['closed_at'] = null;
        }

        $stops = null;
        if (array_key_exists('stops', $data)) {
            $stops = $data['stops'];
            unset($data['stops']);
        }

        $deal->update($data);

        if (is_array($stops)) {
            $this->syncStops($deal, $stops);
        }

        $deal->load(['stops']);

        if (! $deal->wasChanged() && $stops === null) {
            return $this->success('No changes detected', $deal);
        }

        return $this->success('Deal updated successfully', $deal);
    }

    public function destroy(Deal $deal)
    {
        $this->authorize('delete', $deal);

        $deleted = $deal->delete();

        if (! $deleted) {
            return $this->error('Deal could not be deleted');
        }

        return $this->success('Deal deleted successfully');
    }

    private function syncStops(Deal $deal, array $stops): void
    {
        $pickCount = collect($stops)->where('type', 'pick')->count();
        $dropCount = collect($stops)->where('type', 'drop')->count();

        if ($pickCount < 1 || $dropCount < 1) {
            abort(422, 'Stops must include at least 1 pick and 1 drop.');
        }

        // replace-all
        $deal->stops()->delete();

        $rows = [];
        foreach ($stops as $i => $stop) {
            $rows[] = [
                'deal_id' => $deal->id,
                'sequence' => $i + 1,
                'type' => $stop['type'],
                'city' => $stop['city'] ?? null,
                'state' => $stop['state'] ?? null,
                'zip' => $stop['zip'] ?? null,
                'date' => $stop['date'] ?? null,
                'note' => $stop['note'] ?? null,
                'created_at' => now(),
                'updated_at' => now(),
            ];
        }

        DealStop::insert($rows);

        // keep deal summary fields in sync for list page
        $firstPick = $deal->stops()->where('type', 'pick')->orderBy('sequence')->first();
        $lastDrop  = $deal->stops()->where('type', 'drop')->orderByDesc('sequence')->first();

        $deal->update([
            'origin_city' => $firstPick?->city,
            'origin_state' => $firstPick?->state,
            'origin_zip' => $firstPick?->zip,

            'destination_city' => $lastDrop?->city,
            'destination_state' => $lastDrop?->state,
            'destination_zip' => $lastDrop?->zip,

            'pickup_date' => $firstPick?->date,
            'delivery_date' => $lastDrop?->date,
        ]);
    }
}