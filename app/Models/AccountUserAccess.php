<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AccountUserAccess extends Model
{
    protected $table = 'account_user_access';

    protected $fillable = [
        'account_id',
        'user_id',
        'can_edit',
    ];

    protected $casts = [
        'can_edit' => 'boolean',
    ];

    public function account(): BelongsTo
    {
        return $this->belongsTo(Account::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}