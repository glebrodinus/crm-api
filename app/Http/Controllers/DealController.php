<?php

namespace App\Http\Controllers;

use App\Models\Deal;
use App\Models\DealStop;
use App\Models\DealTrailerType;
use App\Models\Account;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Helpers\DealHelper;

class DealController extends Controller
{
    public function index()
    {
        $this->authorize('viewAny', Deal::class);

        $deals = Deal::query()
            ->where('created_by_user_id', Auth::id())
            ->latest()
            ->get();

        return $this->success('Deals retrieved successfully', $deals);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'account_id' => ['required', 'exists:accounts,id'],
            'contact_id' => ['nullable', 'exists:contacts,id'],

            // optional on create (backend defaults)
            'status' => ['nullable', 'in:pending,requested,quoted,booked,lost,cancelled'],

            'origin_city' => ['nullable', 'string', 'max:255'],
            'origin_state' => ['nullable', 'string', 'size:2'],
            'origin_zip' => ['nullable', 'string', 'max:10'],

            'destination_city' => ['nullable', 'string', 'max:255'],
            'destination_state' => ['nullable', 'string', 'size:2'],
            'destination_zip' => ['nullable', 'string', 'max:10'],

            'commodity' => ['nullable', 'string', 'max:255'],
            'weight' => ['nullable', 'integer', 'min:0'],
            'length' => ['nullable', 'integer', 'min:0'],
            'width' => ['nullable', 'integer', 'min:0'],
            'height' => ['nullable', 'integer', 'min:0'],
            'well_length_required' => ['nullable', 'integer', 'min:0'],

            // planned windows (optional; usually synced from stops)
            'pickup_date_from' => ['nullable', 'date'],
            'pickup_date_to' => ['nullable', 'date'],
            'delivery_date_from' => ['nullable', 'date'],
            'delivery_date_to' => ['nullable', 'date'],

            'trip_days' => ['nullable', 'integer', 'min:0', 'max:365'],

            // actual execution
            'actual_pickup_at' => ['nullable', 'date'],
            'actual_delivery_at' => ['nullable', 'date'],

            'distance_miles' => ['nullable', 'integer', 'min:0'],
            'cargo_value' => ['nullable', 'numeric', 'min:0', 'max:99999999.99'],

            'is_partial' => ['nullable', 'boolean'],
            'is_divisible' => ['nullable', 'boolean'],
            'is_oversize' => ['nullable', 'boolean'],
            'is_overweight' => ['nullable', 'boolean'],
            'is_tarp_required' => ['nullable', 'boolean'],
            'is_team' => ['nullable', 'boolean'],
            'is_government' => ['nullable', 'boolean'],
            'is_non_operational' => ['nullable', 'boolean'],
            'is_hazardous' => ['nullable', 'boolean'],
            'is_driver_assist_required' => ['nullable', 'boolean'],
            'is_ramps_required' => ['nullable', 'boolean'],
            'is_twic_required' => ['nullable', 'boolean'],
            'is_emodal_required' => ['nullable', 'boolean'],
            'is_bonded_carrier_required' => ['nullable', 'boolean'],

            'is_temp_required' => ['nullable', 'boolean'],
            'temperature_from' => ['nullable', 'integer', 'min:-100', 'max:150'],
            'temperature_to' => ['nullable', 'integer', 'min:-100', 'max:150'],

            'customer_rate' => ['nullable', 'numeric', 'min:0'],
            'carrier_rate' => ['nullable', 'numeric', 'min:0'],
            'suggested_carrier_rate' => ['nullable', 'numeric', 'min:0'],

            'lost_rate' => ['nullable', 'numeric', 'min:0'],
            'lost_reason' => ['nullable', 'string', 'max:255'],
            'lost_at' => ['nullable', 'date'],

            'company_profit' => ['nullable', 'numeric'],
            'agent_profit' => ['nullable', 'numeric'],
            'agent_commission_percent' => ['nullable', 'numeric', 'min:0', 'max:100'],

            // acceptance (recorded by your agent)
            'customer_accepted_at' => ['nullable', 'date'],
            'customer_accepted_method' => ['nullable', 'string', 'max:20'],

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
            'trailer_types' => ['required', 'array', 'min:1', 'max:7'],
            'trailer_types.*' => ['in:RGN,F,SD,HS,R,V,CN,PO'],
        ]);

        $account = Account::findOrFail($data['account_id']);
        $this->authorize('update', $account);

        // temp range validation
        if (!empty($data['is_temp_required']) && isset($data['temperature_from'], $data['temperature_to'])) {
            if ((int)$data['temperature_from'] > (int)$data['temperature_to']) {
                return $this->error('Temperature from cannot be greater than temperature to.', [], 422);
            }
        }

        $status = $data['status'] ?? 'requested';

        // Extract embedded arrays
        $stops = $data['stops'] ?? null;
        $trailerTypes = $data['trailer_types'] ?? null;

        unset($data['stops'], $data['trailer_types']);

        // acceptance recorded by agent
        if (!empty($data['customer_accepted_at'])) {
            $data['customer_accepted_by_user_id'] = Auth::id();
        }

        // status closure logic
        $data = $this->applyCloseAndLostFields($data, $status);

        // FIX: merge rpm snapshots (do NOT overwrite $data)
        $data = array_merge($data, $this->applyRpmSnapshots($data));

        $deal = Deal::create([
            ...$data,
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

        // recompute rpm snapshots again (in case distance/rates were set, or stops updated summary dates)
        $deal->update($this->applyRpmSnapshots($deal->fresh()->toArray()));

        $deal->load(['account', 'contact', 'stops', 'trailerTypes:id,deal_id,type']);

        DealHelper::transformSingleDeal($deal);

        return $this->success('Deal created successfully', $deal, 201);
    }

    public function show(Deal $deal)
    {
        $this->authorize('view', $deal);

        $deal->load([
            'account',
            'contact',
            'stops',
            'trailerTypes:id,deal_id,type',
            'quotes',
            'carrierQuotes',
            'tasks',
            'activities',
            'notes',
        ]);

        DealHelper::transformSingleDeal($deal);

        unset($deal->trailerTypes);

        return $this->success('Deal retrieved successfully', $deal);
    }

    public function update(Request $request, Deal $deal)
    {
        $this->authorize('update', $deal);

        $data = $request->validate([
            'contact_id' => ['nullable', 'exists:contacts,id'],

            'status' => ['required', 'in:pending,requested,quoted,booked,lost,cancelled'],

            'origin_city' => ['nullable', 'string', 'max:255'],
            'origin_state' => ['nullable', 'string', 'size:2'],
            'origin_zip' => ['nullable', 'string', 'max:10'],

            'destination_city' => ['nullable', 'string', 'max:255'],
            'destination_state' => ['nullable', 'string', 'size:2'],
            'destination_zip' => ['nullable', 'string', 'max:10'],

            'commodity' => ['nullable', 'string', 'max:255'],
            'weight' => ['nullable', 'integer', 'min:0'],
            'length' => ['nullable', 'integer', 'min:0'],
            'width' => ['nullable', 'integer', 'min:0'],
            'height' => ['nullable', 'integer', 'min:0'],
            'well_length_required' => ['nullable', 'integer', 'min:0'],

            'pickup_date_from' => ['nullable', 'date'],
            'pickup_date_to' => ['nullable', 'date'],
            'delivery_date_from' => ['nullable', 'date'],
            'delivery_date_to' => ['nullable', 'date'],

            'trip_days' => ['nullable', 'integer', 'min:0', 'max:365'],

            'actual_pickup_at' => ['nullable', 'date'],
            'actual_delivery_at' => ['nullable', 'date'],

            'distance_miles' => ['nullable', 'integer', 'min:0'],
            'cargo_value' => ['nullable', 'numeric', 'min:0', 'max:99999999.99'],

            'is_partial' => ['nullable', 'boolean'],
            'is_divisible' => ['nullable', 'boolean'],
            'is_oversize' => ['nullable', 'boolean'],
            'is_overweight' => ['nullable', 'boolean'],
            'is_tarp_required' => ['nullable', 'boolean'],
            'is_team' => ['nullable', 'boolean'],
            'is_government' => ['nullable', 'boolean'],
            'is_non_operational' => ['nullable', 'boolean'],
            'is_hazardous' => ['nullable', 'boolean'],
            'is_driver_assist_required' => ['nullable', 'boolean'],
            'is_ramps_required' => ['nullable', 'boolean'],
            'is_twic_required' => ['nullable', 'boolean'],
            'is_emodal_required' => ['nullable', 'boolean'],
            'is_bonded_carrier_required' => ['nullable', 'boolean'],

            'is_temp_required' => ['nullable', 'boolean'],
            'temperature_from' => ['nullable', 'integer', 'min:-100', 'max:150'],
            'temperature_to' => ['nullable', 'integer', 'min:-100', 'max:150'],

            'customer_rate' => ['nullable', 'numeric', 'min:0'],
            'carrier_rate' => ['nullable', 'numeric', 'min:0'],
            'suggested_carrier_rate' => ['nullable', 'numeric', 'min:0'],

            'lost_rate' => ['nullable', 'numeric', 'min:0'],
            'lost_reason' => ['nullable', 'string', 'max:255'],
            'lost_at' => ['nullable', 'date'],

            'company_profit' => ['nullable', 'numeric'],
            'agent_profit' => ['nullable', 'numeric'],
            'agent_commission_percent' => ['nullable', 'numeric', 'min:0', 'max:100'],

            'customer_accepted_at' => ['nullable', 'date'],
            'customer_accepted_method' => ['nullable', 'string', 'max:20'],

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
            'trailer_types.*' => ['in:RGN,F,SD,HS,R,V,CN,PO'],
        ]);

        // temp range validation
        $tempRequired = $request->boolean('is_temp_required', (bool)$deal->is_temp_required);
        $tempFrom = $request->input('temperature_from', $deal->temperature_from);
        $tempTo = $request->input('temperature_to', $deal->temperature_to);
        if ($tempRequired && !is_null($tempFrom) && !is_null($tempTo) && (int)$tempFrom > (int)$tempTo) {
            return $this->error('Temperature from cannot be greater than temperature to.', [], 422);
        }

        $status = $data['status'];

        // Extract embedded arrays (only when present)
        $stops = array_key_exists('stops', $data) ? $data['stops'] : null;
        $trailerTypes = array_key_exists('trailer_types', $data) ? $data['trailer_types'] : null;

        unset($data['stops'], $data['trailer_types']);

        // acceptance recorded by agent
        if (array_key_exists('customer_accepted_at', $data) && !empty($data['customer_accepted_at'])) {
            $data['customer_accepted_by_user_id'] = Auth::id();
        }

        // status closure logic
        $data = $this->applyCloseAndLostFields($data, $status);

        // compute rpm snapshots (merge with existing)
        $merged = array_merge($deal->toArray(), $data);
        $data = array_merge($data, $this->applyRpmSnapshots($merged));

        $deal->update($data);

        if (is_array($stops)) {
            $this->syncStops($deal, $stops);
        }

        if (is_array($trailerTypes)) {
            $this->syncTrailerTypes($deal, $trailerTypes);
        }

        // refresh and ensure rpms correct after potential distance/rate updates
        $deal->refresh();
        $deal->update($this->applyRpmSnapshots($deal->toArray()));

        $deal->load(['account', 'contact', 'stops', 'trailerTypes:id,deal_id,type']);

        DealHelper::transformSingleDeal($deal);

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

    private function applyCloseAndLostFields(array $data, string $status): array
    {
        $closedStatuses = ['lost', 'cancelled'];

        if (in_array($status, $closedStatuses, true)) {
            $data['closed_at'] = $data['closed_at'] ?? now();
        } else {
            $data['closed_at'] = null;
        }

        if ($status === 'lost') {
            $data['lost_at'] = $data['lost_at'] ?? now();
        } else {
            // clear lost marker; keep intel if provided
            $data['lost_at'] = null;
            $data['lost_reason'] = $data['lost_reason'] ?? null;
            $data['lost_rate'] = $data['lost_rate'] ?? null;
        }

        return $data;
    }

    private function applyRpmSnapshots(array $data): array
    {
        $distance = isset($data['distance_miles']) ? (int)$data['distance_miles'] : 0;

        $customerRate = $data['customer_rate'] ?? null;
        $carrierRate = $data['carrier_rate'] ?? null;
        $suggestedCarrierRate = $data['suggested_carrier_rate'] ?? null;

        $out = [];

        if ($distance > 0) {
            $out['customer_rpm'] = is_numeric($customerRate) ? round(((float)$customerRate) / $distance, 3) : null;
            $out['carrier_rpm'] = is_numeric($carrierRate) ? round(((float)$carrierRate) / $distance, 3) : null;
            $out['suggested_carrier_rpm'] = is_numeric($suggestedCarrierRate) ? round(((float)$suggestedCarrierRate) / $distance, 3) : null;
        } else {
            $out['customer_rpm'] = null;
            $out['carrier_rpm'] = null;
            $out['suggested_carrier_rpm'] = null;
        }

        return $out;
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

        // sync summary fields
        $firstPick = $deal->stops()->where('type', 'pick')->orderBy('sequence')->first();
        $lastDrop  = $deal->stops()->where('type', 'drop')->orderByDesc('sequence')->first();

        // For now: stop has single "date" => window from/to is same date
        $pickupDate = $firstPick?->date;
        $deliveryDate = $lastDrop?->date;

        $deal->update([
            'origin_city' => $firstPick?->city,
            'origin_state' => $firstPick?->state,
            'origin_zip' => $firstPick?->zip,

            'destination_city' => $lastDrop?->city,
            'destination_state' => $lastDrop?->state,
            'destination_zip' => $lastDrop?->zip,

            'pickup_date_from' => $pickupDate,
            'pickup_date_to' => $pickupDate,

            'delivery_date_from' => $deliveryDate,
            'delivery_date_to' => $deliveryDate,
        ]);
    }
}