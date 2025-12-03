<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class WalletTransactionAlert extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(
        public string $username,
        public string $typeLabel,
        public string $amount,
        public ?string $description = null,
    ) {
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: __('mail.wallet_transaction.subject'),
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'mail.wallet-transaction-alert',
            with: [
                'username' => $this->username,
                'type' => $this->typeLabel,
                'amount' => $this->amount,
                'description' => $this->description,
            ]
        );
    }

    public function attachments(): array
    {
        return [];
    }
}


