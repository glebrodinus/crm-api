<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DealQuote extends Model
{
    protected $table = 'deal_quotes';

    protected $fillable = [
        'deal_id',
        'created_by_user_id',
        'status',
        'customer_rate',
        'fuel_surcharge',
        'accessorials',
        'note',
        'sent_at',
        'expires_at',
        'selected_at',
    ];

    protected $casts = [
        'accessorials' => 'array',
        'sent_at' => 'datetime',
        'expires_at' => 'datetime',
        'selected_at' => 'datetime',
        'customer_rate' => 'decimal:2',
        'fuel_surcharge' => 'decimal:2',
    ];

    // optional computed badge
    public function getIsExpiredAttribute(): bool
    {
        if ($this->status !== 'sent') return false;
        if (!$this->expires_at) return false;
        return $this->expires_at->isPast();
    }

    public function deal(): BelongsTo
    {
        return $this->belongsTo(Deal::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by_user_id');
    }
}