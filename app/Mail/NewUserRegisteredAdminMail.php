<?php

namespace App\Mail;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class NewUserRegisteredAdminMail extends Mailable
{
    use Queueable, SerializesModels;

    // Send through the dedicated 'auth' mailer (config/mail.php) — the
    // Resend transport with the noreply@linkinablink.com from-address.
    // NOTE: no type declaration — the parent Mailable declares $mailer
    // untyped, and PHP fatals if a child adds a type to an inherited
    // untyped property ("Type of ...::$mailer must not be defined").
    public $mailer = 'auth';

    public function __construct(
        public User $newUser,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'New user on Linkinablink: ' . $this->newUser->name,
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'emails.new-user-registered',
        );
    }
}
