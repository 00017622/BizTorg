<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class VerificationCodeMail extends Mailable
{
    use Queueable, SerializesModels;

    public $code;
    public $email;

    public function __construct($code, $email)
    {
        $this->code = $code;
        $this->email = $email;
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Your BizTorg Verification Code',
            from: new \Illuminate\Mail\Mailables\Address(
                env('MAIL_FROM_ADDRESS'),
                env('MAIL_FROM_NAME')
            )
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.verification_code',
            with: [
                'code' => $this->code,
                'email' => $this->email,
            ]
        );
    }

    public function attachments(): array
    {
        return [];
    }
}
?>