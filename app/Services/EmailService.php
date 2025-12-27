<?php

namespace App\Services;

use App\Mail\VerificationMail;
use App\Mail\PasswordChangeNotificationMail;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\App;

class EmailService
{
    public function sendVerification(string $email, string $code): void
    {
        if (App::environment('local')) {
            Log::info("DEV ONLY - EMAIL code for {$email}: {$code}");
            return;
        }

        Mail::to($email)->queue(new VerificationMail($code));
        Log::info("Verification email sent to {$email}");
    }

    public function sendPasswordChangeNotification(string $email): void
    {
        if (App::environment('local')) {
            Log::info("DEV ONLY - Password change email would be sent to {$email}");
            return;
        }

        Mail::to($email)->queue(new PasswordChangeNotificationMail());
        Log::info("Password change notification email sent to {$email}");
    }
}