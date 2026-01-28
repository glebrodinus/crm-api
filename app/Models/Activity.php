<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Activity extends Model
{
    protected $fillable = [
        'account_id',
        'contact_id',
        'deal_id',

        'created_by_user_id',

        'type',
        'outcome',
        'voicemail_left',

        'subject',
        'note',

        'contact_phone',
        'contact_phone_extension',
        'contact_email',

        'occurred_at',
    ];

    protected $casts = [
        'occurred_at' => 'datetime',
        'voicemail_left' => 'boolean',
    ];

    /* ================= Relationships ================= */

    public function account(): BelongsTo
    {
        return $this->belongsTo(Account::class);
    }

    public function contact(): BelongsTo
    {
        return $this->belongsTo(Contact::class);
    }

    public function deal(): BelongsTo
    {
        return $this->belongsTo(Deal::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by_user_id');
    }

    /* ================= Helpers ================= */

    public function isConnected(): bool
    {
        return $this->outcome === 'connected';
    }

    public function isCall(): bool
    {
        return $this->type === 'call';
    }
}