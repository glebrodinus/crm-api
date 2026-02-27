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

        'trailer_types',

        'is_oversize',
        'is_overweight',
        'needs_tarp',
        'is_team',
        'is_government',
        'is_non_operational',

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

        'trailer_types' => 'array',

        'is_oversize' => 'boolean',
        'is_overweight' => 'boolean',
        'needs_tarp' => 'boolean',
        'is_team' => 'boolean',
        'is_government' => 'boolean',
        'is_non_operational' => 'boolean',

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

    public function quotes(): HasMany
    {
        return $this->hasMany(DealQuote::class);
    }

    public function carrierQuotes(): HasMany
    {
        return $this->hasMany(CarrierQuote::class);
    }

    public function stops(): HasMany
    {
        return $this->hasMany(DealStop::class)->orderBy('sequence');
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