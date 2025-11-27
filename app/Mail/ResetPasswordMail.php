<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class ResetPasswordMail extends Mailable
{
    use Queueable, SerializesModels;

    public string $token;
    public string $fullName;
    public string $resetUrl;

    public function __construct(string $token, string $fullName)
    {
        $this->token = $token;
        $this->fullName = $fullName;

        $base = env('FRONTEND_URL', config('app.url'));
        $this->resetUrl = rtrim($base, '/') . '/reset-password?token=' . urlencode($token);
    }

    public function build()
    {
        return $this->subject('Reset your password')
            ->view('emails.reset_password')
            ->with([
                'token' => $this->token,
                'fullName' => $this->fullName,
                'resetUrl' => $this->resetUrl,
            ]);
    }
}
