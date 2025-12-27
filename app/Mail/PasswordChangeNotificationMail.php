<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Contracts\Queue\ShouldQueue;
use Carbon\Carbon;

class PasswordChangeNotificationMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public string $timestamp;

    /**
     * Create a new message instance.
     */
    public function __construct()
    {
        $this->timestamp = Carbon::now()
            ->setTimezone(config('app.timezone'))
            ->format('F j, Y, g:i A T');
    }

    /**
     * Build the message.
     */
    public function build()
    {   
        return $this->subject('Password Changed Successfully')
                    ->text('mail.password_change')
                    ->with(['timestamp' => $this->timestamp]);
    }
}