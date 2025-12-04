<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class MetaAdsLowBalanceAlert extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(
        public string $username,
        public string $accountName,
        public string $balance,
        public string $currency,
        public string $threshold,
    ) {
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: __('mail.meta_ads_low_balance.subject'),
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'mail.meta-ads-low-balance',
            with: [
                'username' => $this->username,
                'accountName' => $this->accountName,
                'balance' => $this->balance,
                'currency' => $this->currency,
                'threshold' => $this->threshold,
            ]
        );
    }

    public function attachments(): array
    {
        return [];
    }
}

