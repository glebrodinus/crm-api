<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class TeamInvitation extends Model
{
    use HasFactory;

    protected $fillable = [
        'team_id',
        'invited_by_user_id',
        'email',
        'role',
        'token_hash',
        'expires_at',
    ];

    protected $casts = [
        'expires_at' => 'datetime',
    ];

    /**
     * Team being invited to
     */
    public function team()
    {
        return $this->belongsTo(Team::class);
    }

    /**
     * User who sent the invitation
     */
    public function invitedBy()
    {
        return $this->belongsTo(User::class, 'invited_by_user_id');
    }

    /**
     * Check if invitation is expired
     */
    public function isExpired(): bool
    {
        return now()->greaterThan($this->expires_at);
    }
}