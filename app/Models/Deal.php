<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;

class Deal extends Model
{
    protected $fillable = [
        'account_id',
        'contact_id',

        'owner_user_id',
        'created_by_user_id',

        'status',

        'origin_city',
        'origin_state',
        'origin_zip',

        'destination_city',
        'destination_state',
        'destination_zip',

        'commodity',
        'weight_lbs',

        'pickup_date',
        'delivery_date',

        'distance_miles',
        'rpm',

        'is_oversize',
        'is_overweight',
        'is_tarp_required',
        'is_team',
        'is_government',
        'is_non_operational',

        'is_temp_required',
        'temperature_from',
        'temperature_to',

        'customer_rate',
        'carrier_rate',
        'lost_rate',

        'company_profit',
        'agent_profit',
        'agent_commission_percent',

        'closed_at',
        'note',
    ];

    protected $casts = [
        'pickup_date' => 'date',
        'delivery_date' => 'date',
        'closed_at' => 'datetime',

        'distance_miles' => 'integer',
        'rpm' => 'decimal:2',

        'is_oversize' => 'boolean',
        'is_overweight' => 'boolean',
        'is_tarp_required' => 'boolean',
        'is_team' => 'boolean',
        'is_government' => 'boolean',
        'is_non_operational' => 'boolean',

        'is_temp_required' => 'boolean',
        'temperature_from' => 'integer',
        'temperature_to' => 'integer',

        'customer_rate' => 'decimal:2',
        'carrier_rate' => 'decimal:2',
        'lost_rate' => 'decimal:2',

        'company_profit' => 'decimal:2',
        'agent_profit' => 'decimal:2',
        'agent_commission_percent' => 'decimal:2',
    ];

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
        return $this->belongsTo(User::class, 'owner_user_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by_user_id');
    }

    // Multi-stop info (pick / stop / drop)
    public function stops(): HasMany
    {
        return $this->hasMany(DealStop::class)->orderBy('sequence');
    }

    // Trailer types (RGN, SD, etc) stored in table
    public function trailerTypes(): HasMany
    {
        return $this->hasMany(DealTrailerType::class);
    }

    // Market rates from DAT / Truckstop / etc
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