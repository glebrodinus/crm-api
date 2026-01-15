<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Support\Facades\Auth;

class Note extends Model
{
    protected $fillable = [
        'body',
        'is_pinned',
        'is_private',
        'is_important',
        'created_by_user_id',
    ];

    protected $casts = [
        'is_pinned' => 'boolean',
        'is_private' => 'boolean',
        'is_important' => 'boolean',
    ];

    protected static function booted(): void
    {
        static::creating(function (Note $note) {
            if (!$note->created_by_user_id && Auth::check()) {
                $note->created_by_user_id = Auth::id();
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
}