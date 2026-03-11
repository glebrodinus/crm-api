<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Auth;

class Activity extends Model
{
    protected $fillable = [
        'account_id',
        'contact_id',
        'deal_id',

        'type',
        'outcome',
        'voicemail_left',

        'subject',
        'note',

        'contact_phone',
        'contact_phone_extension',
        'contact_email',

        'direction',

        'occurred_at',

        'created_by_user_id',
        'updated_by_user_id',
    ];

    protected $casts = [
        'occurred_at' => 'datetime',
        'voicemail_left' => 'boolean',
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
}