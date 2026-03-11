<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Support\Facades\Auth;

class Deal extends Model
{
    protected $fillable = [
        'account_id',
        'contact_id',

        'status',

        'origin_city',
        'origin_state',
        'origin_zip',

        'destination_city',
        'destination_state',
        'destination_zip',

        'commodity',
        'weight',
        'length',
        'width',
        'height',
        'well_length_required',

        'pickup_date_from',
        'pickup_date_to',
        'delivery_date_from',
        'delivery_date_to',

        'trip_days',

        'actual_pickup_at',
        'actual_delivery_at',

        'distance_miles',

        'is_partial',

        'is_oversize',
        'is_overweight',
        'is_tarp_required',
        'is_team',
        'is_government',
        'is_non_operational',
        'is_hazardous',
        'is_driver_assist_required',
        'is_divisible',
        'is_ramps_required',

        'is_temp_required',
        'temperature_from',
        'temperature_to',

        'customer_rate',
        'carrier_rate',
        'suggested_carrier_rate',

        'lost_rate',
        'lost_reason',
        'lost_at',

        'customer_rpm',
        'carrier_rpm',
        'suggested_carrier_rpm',

        'company_profit',
        'agent_profit',
        'agent_commission_percent',

        'customer_accepted_at',
        'customer_accepted_by_user_id',
        'customer_accepted_method',

        'closed_at',
        'note',

        'created_by_user_id',
        'updated_by_user_id',
    ];

    protected $casts = [
        'delivery_date_from' => 'date:Y-m-d',
        'delivery_date_to' => 'date:Y-m-d',
        'pickup_date_from' => 'date:Y-m-d',
        'pickup_date_to' => 'date:Y-m-d',

        'trip_days' => 'integer',

        'actual_pickup_at' => 'date:Y-m-d',
        'actual_delivery_at' => 'date:Y-m-d',

        'closed_at' => 'date:Y-m-d',
        'lost_at' => 'date:Y-m-d',
        'customer_accepted_at' => 'date:Y-m-d',

        'distance_miles' => 'integer',

        'is_partial' => 'boolean',
        'is_divisible' => 'boolean',
        'is_oversize' => 'boolean',
        'is_overweight' => 'boolean',
        'is_tarp_required' => 'boolean',
        'is_team' => 'boolean',
        'is_government' => 'boolean',
        'is_non_operational' => 'boolean',
        'is_hazardous' => 'boolean',
        'is_self_load' => 'boolean',
        'is_self_unload' => 'boolean',

        'is_temp_required' => 'boolean',
        'temperature_from' => 'integer',
        'temperature_to' => 'integer',

        'customer_rate' => 'decimal:2',
        'carrier_rate' => 'decimal:2',
        'suggested_carrier_rate' => 'decimal:2',

        'lost_rate' => 'decimal:2',

        'customer_rpm' => 'decimal:3',
        'carrier_rpm' => 'decimal:3',
        'suggested_carrier_rpm' => 'decimal:3',

        'company_profit' => 'decimal:2',
        'agent_profit' => 'decimal:2',
        'agent_commission_percent' => 'decimal:2',
    ];

    protected static function booted(): void
    {
        static::creating(function (Account $account) {
            if (Auth::check()) {
                $account->created_by_user_id = Auth::id();
                $account->updated_by_user_id = Auth::id();
            }
        });

        static::updating(function (Account $account) {
            if (Auth::check()) {
                $account->updated_by_user_id = Auth::id();
            }
        });
    }

    public function account(): BelongsTo
    {
        return $this->belongsTo(Account::class);
    }

    public function contact(): BelongsTo
    {
        return $this->belongsTo(Contact::class);
    }

    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by_user_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by_user_id');
    }

    public function customerAcceptedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'customer_accepted_by_user_id');
    }

    public function stops(): HasMany
    {
        return $this->hasMany(DealStop::class)->orderBy('sequence');
    }

    public function trailerTypes(): HasMany
    {
        return $this->hasMany(DealTrailerType::class);
    }

    public function marketRates(): HasMany
    {
        return $this->hasMany(DealMarketRate::class);
    }

    public function quotes(): HasMany
    {
        return $this->hasMany(DealQuote::class);
    }

    public function carrierQuotes(): HasMany
    {
        return $this->hasMany(CarrierQuote::class);
    }

    public function activities(): HasMany
    {
        return $this->hasMany(Activity::class);
    }

    public function tasks(): HasMany
    {
        return $this->hasMany(Task::class);
    }

    public function notes(): MorphMany
    {
        return $this->morphMany(Note::class, 'noteable');
    }
}