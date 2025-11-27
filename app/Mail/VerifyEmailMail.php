<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class VerifyEmailMail extends Mailable
{
    use Queueable, SerializesModels;

    public $otp;
    public $fullName;

    /**
     * Create a new message instance.
     */
    public function __construct($otp, $fullName)
    {
        $this->otp = $otp;
        $this->fullName = $fullName;
    }

    /**
     * Build the message.
     */
    public function build()
    {
        return $this->subject('Xác minh tài khoản của bạn - TradeHub')
            ->markdown('emails.verify_email')
            ->with([
                'otp' => $this->otp,
                'fullName' => $this->fullName,
            ]);
    }
}
