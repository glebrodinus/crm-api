<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use App\Models\User;
use App\Models\VerificationToken;
use App\Services\EmailService;
use Illuminate\Support\Facades\App;

class AuthController extends Controller
{
    protected $tokenTtl;
    protected EmailService $emailService;

    public function __construct(EmailService $emailService)
    {
        $this->emailService = $emailService;
        $this->tokenTtl = (int) config('options.admin.verification_token_ttl'); 
    }

    /**
     * Register a new user
     */
    public function register(Request $request)
    {
        $request->validate([
            'first_name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users,email',
            'email_verification_token_uuid' => 'required|uuid',
            'password' => 'required|string|min:6',
        ]);

        // Check if the email verification token is valid
        $emailToken = VerificationToken::where('uuid', $request->email_verification_token_uuid)
            ->where('identifier', strtolower($request->email))
            ->first();
        if (!$emailToken || $emailToken->isExpired()) {
            return $this->error('Invalid or expired email verification token', [], 401);
        }
        // Check if the cell phone verification token is valid
        $cellPhoneToken = VerificationToken::where('uuid', $request->cell_phone_verification_token_uuid)
            ->where('identifier', $request->cell_phone)
            ->first();
        if (!$cellPhoneToken || $cellPhoneToken->isExpired()) {
            return $this->error('Invalid or expired cell phone verification token', [], 401);
        }

        $user = User::create([
            'first_name' => $request->first_name,
            'last_name' => $request->last_name,
            'email' => strtolower($request->email),
            'password' => Hash::make($request->password),
        ]);

        $emailToken->delete();
        $cellPhoneToken->delete();

        return $this->success('User registered successfully', [], 201);
    }

    /**
     * User login
     */
    public function login(Request $request)
    {
        $request->validate([
            'email' => 'required|string|email',
            'password' => 'required|string',
        ]);

        $user = User::where('email', strtolower($request->email))->first();

        if (!$user || !Hash::check($request->password, $user->password)) {
            return $this->error('Invalid credentials', [], 401);
        }

        // Add full name to user object
        $user['full_name'] = $user->first_name . ' ' . $user->last_name;

        // Generate Sanctum token
        $token = $user->createToken('auth-token')->plainTextToken;

        return $this->success('Login successful', [
            'token' => $token,
            'token_type' => 'Bearer',
            'user' => $user,
        ]);
    }

    /**
     * Get authenticated user
     */
    public function user(Request $request)
    {
        return $this->success('Authenticated user', $request->user());
    }

    /**
     * Logout and revoke token
     */
    public function logout(Request $request)
    {
        $user = $request->user();

        if ($user) {
            $user->currentAccessToken()->delete(); // Revoke only the current token
            return $this->success('Logged out successfully', [], 200);
        }

        return $this->error('User not authenticated', [], 401);
    }

    /**
     * Create verification token
     */
    public function createToken(Request $request)
    {   
        $validated = $request->validate([
            'identifier' => 'required',
            'delivery_method' => 'required|in:email,sms',
        ]);

        // Remove any existing tokens for this identifier
        VerificationToken::where('identifier', $validated['identifier'])->delete();

        $user = User::where('email', strtolower($request->identifier))->first();

        if ($user) {
            // User already exists with this identifier
            return $this->error('User already exists with this email.', [], 409);
        }

        // Create a new token record

        $plainToken = rand(10000, 99999);

        $token = VerificationToken::create([
            'identifier' => $validated['identifier'], // Use 'identifier'
            'type' => $request->delivery_method,
            'code_hash' => $plainToken,
        ]);

        if($request->delivery_method === 'email') {
            $this->emailService->sendVerification($request->identifier, $plainToken);
        }

        $return = [
            'uuid' => $token->uuid,
        ];

         // For local development environment only
        if (App::environment('local')) {
            $return['code'] = $plainToken;
        }

        // Return response
        return $this->success('Token created successfully', $return, 201);
    }

    /**
     * Verify token
     */
    public function verifyToken(Request $request)
    {
        // Validate query parameters instead of request body
        $validated = $request->validate([
            'code' => 'required|integer|digits:5',
            'uuid' => 'required|uuid',
        ]);
    
        $code = $validated['code'];
        $code_hash = password_hash($code, PASSWORD_BCRYPT);
        $uuid = $validated['uuid'];

        // $2y$12$8F9W1gWDHJZKojTtfcCAGeHvIsAmM8t98eUL9SV7O5eHoq1yM1uL6
        // $2y$12$jJ4ehkjWSB6150czA1oBKeW34mVYWUAesPHFEjfUP7qs/F0MR82Ju

        $test_return = [
            'code' => $code,
            'code_hash' => $code_hash,
            'uuid' => $uuid,
        ];
    
        // Find the token for the given identifier (email)
        $verificationToken = VerificationToken::where('code_hash', $code_hash)
            ->where('uuid', $uuid)
            ->first();
    
        if (!$verificationToken) {
            return $this->error('Token not found', $test_return, 401);
        }

        if($verificationToken->isExpired()) {
            $verificationToken->delete();
            return $this->error('Token has expired', [], 401);
        }

        // Token is valid, proceed to verify the user
        $return = [
            'uuid' => $verificationToken->uuid,
        ];
    
        return $this->success('Token verified successfully', $return, 200);
    }

}