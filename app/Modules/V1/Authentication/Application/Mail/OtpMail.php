<?php

namespace App\Modules\V1\Authentication\Application\Mail;

use App\Modules\V1\Authentication\Domain\ValueObjects\OtpPurposeEnum;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class OtpMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public readonly string $code,
        public readonly OtpPurposeEnum $purpose,
        public readonly ?string $name = null,
        public readonly int $expiresInMinutes = 10,
    ) {
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: __($this->purpose->subjectKey(), ['app' => config('app.name')]),
        );
    }

    public function content(): Content
    {
        $greeting = $this->name
            ? __($this->purpose->greetingKey(), ['name' => $this->name])
            : __('otp.greeting_anonymous');

        return new Content(
            view: 'mail.otp',
            with: [
                'subject' => __($this->purpose->subjectKey(), ['app' => config('app.name')]),
                'greeting' => $greeting,
                'code' => $this->code,
                'expiresInMinutes' => $this->expiresInMinutes,
            ],
        );
    }
}
