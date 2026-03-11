<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserSetting extends Model
{
    protected $fillable = [
        'user_id',
        'minimum_profit_amount',
        'target_margin_percent',
    ];

    protected function casts(): array
    {
        return [
            'minimum_profit_amount' => 'decimal:2',
            'target_margin_percent' => 'decimal:2',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}