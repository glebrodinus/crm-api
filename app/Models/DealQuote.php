<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Auth;

class DealQuote extends Model
{
    protected $table = 'deal_quotes';

    protected $fillable = [
        'deal_id',

        'status',

        'customer_rate',
        'competitor_rate',
        'customer_counter_rate',

        'note',
        'rejected_reason',

        'sent_at',
        'expires_at',
        'accepted_at',
        'rejected_at',

        'created_by_user_id',
        'updated_by_user_id',
    ];

    protected $casts = [
        'sent_at' => 'datetime',
        'expires_at' => 'datetime',
        'accepted_at' => 'datetime',
        'rejected_at' => 'datetime',

        'customer_rate' => 'decimal:2',
        'competitor_rate' => 'decimal:2',
        'customer_counter_rate' => 'decimal:2',
    ];

    protected static function booted(): void
    {
        static::creating(function (DealQuote $dealQuote) {
            if (Auth::check()) {
                $dealQuote->created_by_user_id = Auth::id();
                $dealQuote->updated_by_user_id = Auth::id();
            }
        });

        static::updating(function (DealQuote $dealQuote) {
            if (Auth::check()) {
                $dealQuote->updated_by_user_id = Auth::id();
            }
        });
    }

    public function getIsExpiredAttribute(): bool
    {
        if ($this->status !== 'sent') {
            return false;
        }

        if (!$this->expires_at) {
            return false;
        }

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

    public function updater(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by_user_id');
    }
}