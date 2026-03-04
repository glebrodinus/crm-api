<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Auth;

class Account extends Model
{
    protected $fillable = [
        'team_id',

        'owner_user_id',
        'created_by_user_id',

        'last_contacted_at',
        'last_attempted_at',
        'last_deal_at',

        'name',
        'website',

        'address',
        'address_2',
        'city',
        'state',
        'zip',
        'country',
        'phone',

        'status',

        'is_unreachable',
        'unreachable_at',
        'unreachable_reason',

        'qualified_at',
        'qualified_by_user_id',

        'disqualified_at',
        'disqualified_by_user_id',
        'disqualified_reason',

        'note',
    ];

    protected $casts = [
        'last_contacted_at' => 'datetime',
        'last_attempted_at' => 'datetime',
        'last_deal_at' => 'datetime',

        'is_unreachable' => 'boolean',
        'unreachable_at' => 'datetime',

        'qualified_at' => 'datetime',
        'disqualified_at' => 'datetime',
    ];

    protected static function booted(): void
    {
        static::creating(function (Account $account) {
            if (Auth::check()) {
                $account->created_by_user_id = Auth::id();
                $account->owner_user_id ??= Auth::id();
            }
        });
    }

    public function team(): BelongsTo
    {
        return $this->belongsTo(Team::class);
    }

    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_user_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by_user_id');
    }

    public function qualifiedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'qualified_by_user_id');
    }

    public function disqualifiedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'disqualified_by_user_id');
    }

    public function contacts(): HasMany
    {
        return $this->hasMany(Contact::class);
    }

    public function deals(): HasMany
    {
        return $this->hasMany(Deal::class);
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

    // helpers
    public function isQualified(): bool
    {
        return !is_null($this->qualified_at) && is_null($this->disqualified_at);
    }

    public function isDisqualified(): bool
    {
        return !is_null($this->disqualified_at);
    }
}