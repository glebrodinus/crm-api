<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Auth;

class Account extends Model
{
    protected $fillable = [
        'last_contacted_at',
        'last_attempted_at',
        'last_deal_at',

        'follow_up_at',
        'follow_up_type',
        'follow_up_contact_id',
        'follow_up_note',

        'name',
        'dba_name',
        'website',
        'email',

        'address',
        'address_2',
        'city',
        'state',
        'zip',
        'country',
        'phone',
        'timezone',

        'status',

        'unreachable_at',
        'unreachable_by_user_id',
        'unreachable_reason',

        'qualified_at',
        'qualified_by_user_id',
        'qualified_reason',

        'disqualified_at',
        'disqualified_by_user_id',
        'disqualified_reason',

        'note',

        'created_by_user_id',
        'updated_by_user_id',
    ];

    protected $casts = [
        'last_contacted_at' => 'date:Y-m-d',
        'last_attempted_at' => 'date:Y-m-d',
        'last_deal_at' => 'date:Y-m-d',

        'follow_up_at' => 'date:Y-m-d',

        'unreachable_at' => 'date:Y-m-d',

        'qualified_at' => 'date:Y-m-d',
        'disqualified_at' => 'date:Y-m-d',
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

    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by_user_id');
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

    public function unreachableBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'unreachable_by_user_id');
    }

    public function followUpContact(): BelongsTo
    {
        return $this->belongsTo(Contact::class, 'follow_up_contact_id');
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

    public function allNotes(): MorphMany
    {
        return $this->morphMany(Note::class, 'noteable');
    }

    public function notes(): MorphMany
    {
        return $this->morphMany(Note::class, 'noteable')
            ->where('type', 'note');
    }

    public function links(): MorphMany
    {
        return $this->morphMany(Note::class, 'noteable')
            ->where('type', 'link');
    }

    public function isQualified(): bool
    {
        return !is_null($this->qualified_at) && is_null($this->disqualified_at);
    }

    public function isDisqualified(): bool
    {
        return !is_null($this->disqualified_at);
    }

    public function userAccesses(): HasMany
    {
        return $this->hasMany(AccountUserAccess::class);
    }

    public function accessibleUsers(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'account_user_access')
            ->withPivot(['can_edit'])
            ->withTimestamps();
    }
}