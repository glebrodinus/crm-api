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
     * List all users.
     */
    public function index()
    {
        $users = User::all();
        return $this->success('Users retrieved successfully', $users);
    }

    /**
     * Show a specific user.
     */
    public function show(User $user)
    {
        return $this->success('User retrieved successfully', $user);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        // Using registration endpoint instead
    }

    /**
     * Update a user.
     */
    public function update(Request $request, User $user)
    {
        // Validate the request data

        $validatedData = $request->validate([
            'first_name' => 'sometimes|required|string|max:255',
            'last_name' => 'sometimes|required|string|max:255',
            'email' => 'sometimes|required|string|email|max:255|unique:users,email,' . $user->id,
            // 'password' => 'sometimes|required|string|min:5|confirmed',
        ]);

        // Update the user instance
        $user->update(array_filter($validatedData));

        $return = User::find($user->id);

        return $this->success('User updated successfully', $return);
    }

    /**
     * Delete a user.
     */
    public function destroy(User $user)
    {       
        // LATER WE NEED TO VERIFY PASSWORD BEFORE DELETING USER
        if($user->id === Auth::id()) {
            $user->delete();
            return $this->success('User deleted successfully');
        }

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


    public function updateName(Request $request)
    {
        $request->validate([
            'first_name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
        ]);
    
        /** @var \App\Models\User $user */
        $user = Auth::user();
    
        $user->update([
            'first_name' => $request->first_name,
            'last_name' => $request->last_name,
        ]);
    
        return $this->success('User name updated successfully', $user);
    }

    public function updatePassword(Request $request)
    {
        $request->validate([
            'password' => 'required|string',
            'new_password' => 'required|string|min:5',
        ]);

        /** @var \App\Models\User $user */
        $user = Auth::user();

        if (!Hash::check($request->password, $user->password)) {
            return $this->error('Current password is incorrect', [], 401);
        }

        $user->update(['password' => Hash::make($request->new_password)]);

        return $this->success('Password updated successfully');
    }

    public function updateEmail(Request $request)
    {
        $request->validate([
            'email' => 'required|email|unique:users,email,' . Auth::id(),
            'token_code' => 'required|string',
            'token_uuid' => 'required|uuid',
        ]);

        $token = VerificationToken::where('uuid', $request->token_uuid)
            ->where('identifier', strtolower($request->email))
            ->where('code', $request->token_code)
            ->first();

        if(!$token) {
            return $this->error('Invalid token', [], 401);
        }

        if($token->isExpired()) {
            return $this->error('Token has expired', [], 401);
            $token->delete();
        }

        /** @var \App\Models\User $user */
        $user = Auth::user();
        $user->update(['email' => strtolower($request->email)]);

        return $this->success('Email updated successfully', $user);
    }

    public function deleteAccount(Request $request)
    {
        $request->validate([
            'password' => 'required|string',
        ]);

        /** @var \App\Models\User $user */
        $user = Auth::user();

        if (!Hash::check($request->password, $user->password)) {
            return $this->error('Password is incorrect', [], 401);
        }

        $user->delete();

        return $this->success('User deleted successfully');
    }
}
