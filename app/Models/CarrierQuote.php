<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CarrierQuote extends Model
{
    protected $fillable = [
        'deal_id',
        'created_by_user_id',
        'carrier_name',
        'carrier_mc',
        'carrier_usdot',
        'contact_name',
        'contact_phone',
        'contact_email',
        'carrier_rate',
        'note',
    ];

    protected $casts = [
        'carrier_rate' => 'decimal:2',
    ];

    public function deal(): BelongsTo
    {
        return $this->belongsTo(Deal::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by_user_id');
    }
}