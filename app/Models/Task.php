<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Support\Facades\Auth;

class Task extends Model
{
    protected $fillable = [
        'account_id',
        'contact_id',
        'deal_id',
        'created_by_user_id',
        'assigned_to_user_id',
        'type',
        'title',
        'priority',
        'due_at',
        'completed_at',
        'completed_by_user_id',
    ];

    protected $casts = [
        'priority' => 'integer',
        'due_at' => 'datetime',
        'completed_at' => 'datetime',
    ];

    /**
     * Automatically assign task if not provided
     */
    protected static function booted()
    {
        static::creating(function ($task) {
            if (empty($task->assigned_to_user_id)) {
                $task->assigned_to_user_id = Auth::id() ?? $task->created_by_user_id;
            }
        });
    }

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

    public function assignee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to_user_id');
    }

    public function completedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'completed_by_user_id');
    }

    public function notes(): MorphMany
    {
        return $this->morphMany(Note::class, 'noteable');
    }
}