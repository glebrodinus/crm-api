<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
use Illuminate\Support\Carbon;

class VerificationToken extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'uuid',
        'code_hash',      // 5-digit numeric token
        'identifier', // Can be email or phone
        'expires_at',
    ];

    // Automatically generate a UUID, numeric token, and expiration on creation
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {

            $token_ttl = (int) config('options.admin.verification_token_ttl');

            if (empty($model->uuid)) {
                $model->uuid = Str::uuid(); // Generate a unique UUID
            }

            // Generate a random 5-digit numeric token if not set
            if (empty($model->code_hash)) {
                $plainToken = rand(10000, 99999);
                $model->code_hash = $plainToken;
            }

            // Hash the token before saving
            $model->code_hash = password_hash($model->code_hash, PASSWORD_BCRYPT);

            // Set expiration time using seconds
            if (empty($model->expires_at)) {
                $model->expires_at = Carbon::now()->addSeconds($token_ttl);
            }
        });
    }

    // Helper method to check if the token is expired
    public function isExpired()
    {
        return now()->greaterThan($this->expires_at);
    }
}