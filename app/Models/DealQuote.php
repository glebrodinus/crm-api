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
        'customer_rate','fuel_surcharge',
        'accessorials',
        'notes',
        'sent_at','expires_at',
    ];

    protected $casts = [
        'accessorials' => 'array',
        'sent_at' => 'datetime',
        'expires_at' => 'datetime',
        'customer_rate' => 'decimal:2',
        'fuel_surcharge' => 'decimal:2',
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
