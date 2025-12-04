<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class WalletLowBalanceAlert extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(
        public string $username,
        public string $balance,
        public string $threshold,
    ) {
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: __('mail.wallet_low_balance.subject'),
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'mail.wallet-low-balance',
            with: [
                'username' => $this->username,
                'balance' => $this->balance,
                'threshold' => $this->threshold,
            ]
        );
    }

    public function attachments(): array
    {
        return [];
    }
}

