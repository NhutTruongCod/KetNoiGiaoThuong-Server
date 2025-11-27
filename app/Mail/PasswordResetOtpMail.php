<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class PasswordResetOtpMail extends Mailable
{
    use Queueable, SerializesModels;

    public string $otp;
    public string $fullName;

    public function __construct(string $otp, string $fullName)
    {
        $this->otp = $otp;
        $this->fullName = $fullName;
    }

    public function build()
    {
        return $this->subject('Your password reset OTP')
            ->view('emails.password_reset_otp')
            ->with([
                'otp' => $this->otp,
                'fullName' => $this->fullName,
            ]);
    }
}
