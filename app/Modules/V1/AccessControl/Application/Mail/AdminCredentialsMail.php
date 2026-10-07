<?php

namespace App\Modules\V1\AccessControl\Application\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class AdminCredentialsMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public readonly string $name,
        public readonly string $email,
        public readonly string $password,
        public readonly string $portal,
        public readonly bool $passwordUpdated = false,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: $this->passwordUpdated
                ? 'Your '.config('app.name').' admin password has been updated'
                : 'Your '.config('app.name').' admin account',
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'mail.admin-credentials',
        );
    }
}
