<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Support\Facades\Auth;

class Note extends Model
{
    protected $fillable = [
        'type',
        'content',
        'url',
        'url_label',
        'is_pinned',
        'is_private',
        'is_important',
        'created_by_user_id',
        'updated_by_user_id',
    ];

    protected $casts = [
        'is_pinned' => 'boolean',
        'is_private' => 'boolean',
        'is_important' => 'boolean',
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

    public function noteable(): MorphTo
    {
        return $this->morphTo();
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by_user_id');
    }

    /*
    |--------------------------------
    | Helpers
    |--------------------------------
    */

    public function isNote(): bool
    {
        return $this->type === 'note';
    }

    public function isLink(): bool
    {
        return $this->type === 'link';
    }
}