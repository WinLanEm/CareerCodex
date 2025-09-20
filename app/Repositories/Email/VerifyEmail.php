<?php

namespace App\Repositories\Email;

use App\Contracts\Repositories\Email\GenerateVerificationCodeRepositoryInterface;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class VerifyEmail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        private User $user,
        private GenerateVerificationCodeRepositoryInterface $generateVerificationCode
    )
    {

    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Подтверждение email адреса',
        );
    }

    public function content(): Content
    {
        $code = $this->generateVerificationCode->generate($this->user);
        return new Content(
            view: 'emails.verify',
            with: [
                'user' => $this->user,
                'code' => $code,
            ],
        );
    }

    public function attachments(): array
    {
        return [];
    }
}
