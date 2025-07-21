<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use App\Models\VisaApplication;


class VisaStatusUpdated extends Mailable 
{
    use Queueable, SerializesModels;

    public $visa;

    public function __construct(VisaApplication $visa)
    {
        $this->visa = $visa;
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Your Visa Status Has Been Updated',
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'mail.visa-status-updated',
            with: [
                'status' => $this->visa->status,
                'name' => $this->visa->name,
            ],
        );
    }

    public function attachments(): array
    {
        return [];
    }
}
