<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class ServiceUserStatusAlert extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(
        public string $username,
        public string $packageName,
        public string $statusKey,
    ) {
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: __('service_user.mail.subject'),
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'mail.service-user-status-alert',
            with: [
                'username' => $this->username,
                'package' => $this->packageName,
                'statusKey' => $this->statusKey,
            ]
        );
    }

    public function attachments(): array
    {
        return [];
    }
}


