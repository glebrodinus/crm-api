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
        'owner_user_id',
        'name','website','city','state','zip', 'address','address_2','country','phone',
        'status',
        'is_qualified','qualified_at','qualified_by_user_id',
        'is_blocked','blocked_reason','blocked_at','blocked_by_user_id',
        'last_contacted_at', 'note',
    ];

    protected $casts = [
        'is_qualified' => 'boolean',
        'qualified_at' => 'datetime',
        'is_blocked' => 'boolean',
        'blocked_at' => 'datetime',
        'last_contacted_at' => 'datetime',
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

    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_user_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by_user_id');
    }

    public function blockedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'blocked_by_user_id');
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
}