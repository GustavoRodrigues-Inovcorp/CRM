<?php

namespace App\Mail;

use App\Models\DealProposal;
use App\Models\Deal;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Queue\SerializesModels;

class ProposalMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public Deal $deal,
        public DealProposal $proposal,
        public string $body,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Proposta Comercial — ' . $this->deal->title,
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.proposal',
        );
    }

    public function attachments(): array
    {
        return [
            Attachment::fromStorageDisk('public', $this->proposal->file_path)
                ->as($this->proposal->file_name)
                ->withMime('application/pdf'),
        ];
    }
}