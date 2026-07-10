<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class AdminTemporaryPasswordMail extends Mailable
{
    use Queueable, SerializesModels;

    // Send through the dedicated 'auth' mailer (config/mail.php).
    // NOTE: no type declaration — the parent Mailable declares $mailer
    // untyped, and PHP fatals if a child adds a type to an inherited
    // untyped property ("Type of ...::$mailer must not be defined").
    public $mailer = 'auth';

    public function __construct(
        public string $userName,
        public string $temporaryPassword,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Your password has been reset',
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'emails.admin-temporary-password',
        );
    }
}
