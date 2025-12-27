<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use App\Models\VerificationToken;
use App\Services\EmailService;
use Illuminate\Support\Facades\Auth;

class UserController extends Controller
{
    protected EmailService $emailService;

    public function __construct(EmailService $emailService)
    {
        $this->emailService = $emailService;
    }

    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        //
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }

    /**
     * Check if a user exists by email
     */
    public function checkUserExistsByEmail(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
        ]);

        $email = strtolower($request->query('email'));
        $userExists = User::where('email', $email)->exists();

        return $userExists
            ? $this->success('User exists', ['exists' => true])
            : $this->error('User not found', ['exists' => false], 404);
    }

    /**
     * Reset user password
     */
    public function resetPassword(Request $request)
    {
        $request->validate([
            'email' => 'required|email|exists:users,email',
            'password' => 'required|string|min:6|confirmed',
            'verification_token_uuid' => 'required|uuid',
        ]);

        $verificationToken = VerificationToken::where('uuid', $request->verification_token_uuid)
            ->where('identifier', strtolower($request->email))
            ->first();

        if (!$verificationToken) {
            return $this->error('Invalid or expired verification token', [], 401);
        }

        if($verificationToken->isExpired()) {
            return $this->error('Verification token has expired', [], 401);
            $verificationToken->delete();
        }
        
        // Check if the email matches the token identifier
        if( $request->email !== $verificationToken->identifier) {
            return $this->error('Email does not match the token identifier', [], 401);
        }

        // Token is valid, proceed to reset the password
        // Find the user by email
        $user = User::where('email', $request->email)->firstOrFail();

        if(!$user) {
            return $this->error('User not found', [], 404);
        }

        // Hash the password the same way Fortify does
        $user->update([
            'password' => Hash::make($request->password),
        ]);

        // Remove the password reset token
        $verificationToken->delete();

        // Send a password changed email
        $this->emailService->sendPasswordChangeNotification($request->email);
    
        return $this->success('Password changed successfully');
    }
}
