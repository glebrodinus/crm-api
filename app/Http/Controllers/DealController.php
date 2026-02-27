<?php

namespace App\Http\Controllers;

use App\Models\Deal;
use App\Models\DealStop;
use App\Models\DealTrailerType;
use App\Models\DealMarketRate;
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

            // snapshots (optional; can be auto-synced from stops)
            'origin_city' => ['nullable', 'string', 'max:255'],
            'origin_state' => ['nullable', 'string', 'size:2'],
            'origin_zip' => ['nullable', 'string', 'max:10'],

            'destination_city' => ['nullable', 'string', 'max:255'],
            'destination_state' => ['nullable', 'string', 'size:2'],
            'destination_zip' => ['nullable', 'string', 'max:10'],

            'commodity' => ['nullable', 'string', 'max:255'],
            'weight_lbs' => ['nullable', 'integer', 'min:0'],

            // summary dates (optional; can be auto-synced from stops)
            'pickup_date' => ['nullable', 'date'],
            'delivery_date' => ['nullable', 'date'],

            // distance / rpm
            'distance_miles' => ['nullable', 'integer', 'min:0'],
            'rpm' => ['nullable', 'numeric', 'min:0'],

            // flags
            'is_oversize' => ['nullable', 'boolean'],
            'is_overweight' => ['nullable', 'boolean'],
            'is_tarp_required' => ['nullable', 'boolean'],
            'is_team' => ['nullable', 'boolean'],
            'is_government' => ['nullable', 'boolean'],
            'is_non_operational' => ['nullable', 'boolean'],

            // temperature
            'is_temp_required' => ['nullable', 'boolean'],
            'temperature_from' => ['nullable', 'integer', 'min:-100', 'max:150'],
            'temperature_to' => ['nullable', 'integer', 'min:-100', 'max:150'],

            // money
            'customer_rate' => ['nullable', 'numeric', 'min:0'],
            'carrier_rate' => ['nullable', 'numeric', 'min:0'],
            'lost_rate' => ['nullable', 'numeric', 'min:0'],

            'company_profit' => ['nullable', 'numeric'],
            'agent_profit' => ['nullable', 'numeric'],
            'agent_commission_percent' => ['nullable', 'numeric', 'min:0', 'max:100'],

            'note' => ['nullable', 'string', 'max:255'],
            'closed_at' => ['nullable', 'date'],

            // embedded stops (pick/stop/drop)
            'stops' => ['nullable', 'array', 'min:2', 'max:10'],
            'stops.*.type' => ['required_with:stops', 'in:pick,drop,stop'],
            'stops.*.city' => ['nullable', 'string', 'max:255'],
            'stops.*.state' => ['nullable', 'string', 'size:2'],
            'stops.*.zip' => ['nullable', 'string', 'max:10'],
            'stops.*.date' => ['nullable', 'date'],
            'stops.*.note' => ['nullable', 'string', 'max:255'],

            // trailer types (stored in table)
            'trailer_types' => ['nullable', 'array', 'min:1', 'max:7'],
            'trailer_types.*' => ['in:RGN,F,SD,HS,R,V,CN'],

            // market rates (DAT / Truckstop / etc)
            'market_rates' => ['nullable', 'array', 'max:20'],
            'market_rates.*.source' => ['required_with:market_rates', 'string', 'max:50'],
            'market_rates.*.low_rate' => ['nullable', 'numeric', 'min:0'],
            'market_rates.*.avg_rate' => ['nullable', 'numeric', 'min:0'],
            'market_rates.*.high_rate' => ['nullable', 'numeric', 'min:0'],
            'market_rates.*.pulled_at' => ['nullable', 'date'],
            'market_rates.*.note' => ['nullable', 'string', 'max:255'],
        ]);

        $account = Account::findOrFail($data['account_id']);
        $this->authorize('update', $account);

        $status = $data['status'] ?? 'requested';

        if ($status === 'lost' && empty($data['closed_at'])) {
            $data['closed_at'] = now();
        }
        if ($status !== 'lost') {
            $data['closed_at'] = null;
        }

        // validate temp range if temp required
        if (!empty($data['is_temp_required']) && isset($data['temperature_from'], $data['temperature_to'])) {
            if ((int)$data['temperature_from'] > (int)$data['temperature_to']) {
                return $this->error('Temperature from cannot be greater than temperature to.', [], 422);
            }
        }

        // Extract embedded arrays
        $stops = $data['stops'] ?? null;
        $trailerTypes = $data['trailer_types'] ?? null;
        $marketRates = $data['market_rates'] ?? null;

        unset($data['stops'], $data['trailer_types'], $data['market_rates']);

        // compute rpm if not provided
        $data['rpm'] = $this->computeRpm(
            $data['rpm'] ?? null,
            $data['customer_rate'] ?? null,
            $data['distance_miles'] ?? null
        );

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

        if (is_array($trailerTypes)) {
            $this->syncTrailerTypes($deal, $trailerTypes);
        }

        if (is_array($marketRates)) {
            $this->syncMarketRates($deal, $marketRates);
        }

        $deal->load(['account', 'contact', 'stops', 'trailerTypes', 'marketRates']);

        return $this->success('Deal created successfully', $deal, 201);
    }

    public function show(Deal $deal)
    {
        $this->authorize('view', $deal);

        $deal->load([
            'account',
            'contact',
            'stops',
            'trailerTypes',
            'marketRates',
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

            'distance_miles' => ['nullable', 'integer', 'min:0'],
            'rpm' => ['nullable', 'numeric', 'min:0'],

            'is_oversize' => ['nullable', 'boolean'],
            'is_overweight' => ['nullable', 'boolean'],
            'is_tarp_required' => ['nullable', 'boolean'],
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

            // embedded updates (only if provided)
            'stops' => ['sometimes', 'array', 'min:2', 'max:10'],
            'stops.*.type' => ['required_with:stops', 'in:pick,drop,stop'],
            'stops.*.city' => ['nullable', 'string', 'max:255'],
            'stops.*.state' => ['nullable', 'string', 'size:2'],
            'stops.*.zip' => ['nullable', 'string', 'max:10'],
            'stops.*.date' => ['nullable', 'date'],
            'stops.*.note' => ['nullable', 'string', 'max:255'],

            'trailer_types' => ['sometimes', 'array', 'min:1', 'max:7'],
            'trailer_types.*' => ['in:RGN,F,SD,HS,R,V,CN'],

            'market_rates' => ['sometimes', 'array', 'max:20'],
            'market_rates.*.source' => ['required_with:market_rates', 'string', 'max:50'],
            'market_rates.*.low_rate' => ['nullable', 'numeric', 'min:0'],
            'market_rates.*.avg_rate' => ['nullable', 'numeric', 'min:0'],
            'market_rates.*.high_rate' => ['nullable', 'numeric', 'min:0'],
            'market_rates.*.pulled_at' => ['nullable', 'date'],
            'market_rates.*.note' => ['nullable', 'string', 'max:255'],
        ]);

        if ($data['status'] === 'lost' && empty($data['closed_at'])) {
            $data['closed_at'] = now();
        }
        if ($data['status'] !== 'lost') {
            $data['closed_at'] = null;
        }

        // validate temp range if temp required
        $tempRequired = $request->boolean('is_temp_required', (bool)$deal->is_temp_required);
        $tempFrom = $request->input('temperature_from', $deal->temperature_from);
        $tempTo = $request->input('temperature_to', $deal->temperature_to);
        if ($tempRequired && !is_null($tempFrom) && !is_null($tempTo) && (int)$tempFrom > (int)$tempTo) {
            return $this->error('Temperature from cannot be greater than temperature to.', [], 422);
        }

        // Extract embedded arrays (only when present)
        $stops = array_key_exists('stops', $data) ? $data['stops'] : null;
        $trailerTypes = array_key_exists('trailer_types', $data) ? $data['trailer_types'] : null;
        $marketRates = array_key_exists('market_rates', $data) ? $data['market_rates'] : null;

        unset($data['stops'], $data['trailer_types'], $data['market_rates']);

        // compute rpm if not provided
        $customerRate = array_key_exists('customer_rate', $data) ? $data['customer_rate'] : $deal->customer_rate;
        $distanceMiles = array_key_exists('distance_miles', $data) ? $data['distance_miles'] : $deal->distance_miles;

        $data['rpm'] = $this->computeRpm(
            $data['rpm'] ?? null,
            $customerRate,
            $distanceMiles
        );

        $deal->update($data);

        if (is_array($stops)) {
            $this->syncStops($deal, $stops);
        }

        if (is_array($trailerTypes)) {
            $this->syncTrailerTypes($deal, $trailerTypes);
        }

        if (is_array($marketRates)) {
            $this->syncMarketRates($deal, $marketRates);
        }

        $deal->load(['account', 'contact', 'stops', 'trailerTypes', 'marketRates']);

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

    private function computeRpm($rpm, $customerRate, $distanceMiles): ?float
    {
        if (!is_null($rpm)) {
            return round((float) $rpm, 2);
        }

        if ($customerRate && $distanceMiles && (int) $distanceMiles > 0) {
            return round(((float) $customerRate) / ((int) $distanceMiles), 2);
        }

        return null;
    }

    private function syncTrailerTypes(Deal $deal, array $types): void
    {
        $types = array_values(array_unique($types));

        $deal->trailerTypes()->delete();

        $rows = [];
        foreach ($types as $type) {
            $rows[] = [
                'deal_id' => $deal->id,
                'type' => $type,
                'created_at' => now(),
                'updated_at' => now(),
            ];
        }

        if (!empty($rows)) {
            DealTrailerType::insert($rows);
        }
    }

    private function syncMarketRates(Deal $deal, array $rates): void
    {
        $deal->marketRates()->delete();

        $rows = [];
        foreach ($rates as $r) {
            $source = strtoupper(trim((string)($r['source'] ?? '')));
            if ($source === '') {
                continue;
            }

            $rows[] = [
                'deal_id' => $deal->id,
                'source' => $source,
                'low_rate' => $r['low_rate'] ?? null,
                'avg_rate' => $r['avg_rate'] ?? null,
                'high_rate' => $r['high_rate'] ?? null,
                'pulled_at' => $r['pulled_at'] ?? null,
                'note' => $r['note'] ?? null,
                'created_at' => now(),
                'updated_at' => now(),
            ];
        }

        if (!empty($rows)) {
            DealMarketRate::insert($rows);
        }
    }

    private function syncStops(Deal $deal, array $stops): void
    {
        $pickCount = collect($stops)->where('type', 'pick')->count();
        $dropCount = collect($stops)->where('type', 'drop')->count();

        if ($pickCount < 1 || $dropCount < 1) {
            abort(422, 'Stops must include at least 1 pick and 1 drop.');
        }

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

        // sync summary fields for fast lists
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