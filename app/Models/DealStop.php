<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DealStop extends Model
{
    protected $fillable = [
        'deal_id',
        'sequence',
        'type',
        'city',
        'state',
        'zip',
        'date',
        'note',
    ];

    protected $casts = [
        'date' => 'date',
    ];

    public function deal(): BelongsTo
    {
        return $this->belongsTo(Deal::class);
    }
}