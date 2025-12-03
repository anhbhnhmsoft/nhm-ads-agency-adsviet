<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class AdminWalletTransactionAlert extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(
        public string $adminName,
        public string $customerName,
        public string $transactionType,
        public string $amount,
        public ?string $stage = null,
        public ?string $description = null,
    ) {
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: __('mail.admin_wallet_transaction.subject'),
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'mail.admin-wallet-transaction-alert',
            with: [
                'adminName' => $this->adminName,
                'customerName' => $this->customerName,
                'transactionType' => $this->transactionType,
                'amount' => $this->amount,
                'stage' => $this->stage,
                'description' => $this->description,
            ]
        );
    }

    public function attachments(): array
    {
        return [];
    }
}


