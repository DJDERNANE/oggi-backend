<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class PasswordResetLink extends Mailable
{
    use Queueable, SerializesModels;

    public $user;
    public $token;
    public $resetUrl;

    public function __construct($user, $token)
    {
        $this->user = $user;
        $this->token = $token;
        $this->resetUrl = config('app.frontend_url').'/reset-password?token='.$token.'&email='.$user->email;
    }

    public function build()
    {
        return $this->subject('Password Reset Request')
                    ->view('emails.password_reset');
    }
}