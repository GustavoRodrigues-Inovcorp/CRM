<?php

namespace App\Mail;

use App\Models\Deal;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class FollowUpMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public Deal $deal,
        public string $emailBody,
        public int $followUpNumber,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Seguimento — ' . $this->deal->title,
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.followup',
        );
    }
}