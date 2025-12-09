<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class MetaAdsSpendingExceededAlert extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(
        public string $username,
        public string $accountName,
        public string $spending,
        public string $balance,
        public string $threshold, // Ngưỡng an toàn (100)
        public string $limit, // Giới hạn tổng (balance + threshold)
        public string $currency,
    ) {
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: __('mail.meta_ads_spending_exceeded.subject'),
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'mail.meta-ads-spending-exceeded',
            with: [
                'username' => $this->username,
                'accountName' => $this->accountName,
                'spending' => $this->spending,
                'balance' => $this->balance,
                'threshold' => $this->threshold, // Ngưỡng an toàn (100)
                'limit' => $this->limit, // Giới hạn tổng (150)
                'currency' => $this->currency,
            ]
        );
    }

    public function attachments(): array
    {
        return [];
    }
}

