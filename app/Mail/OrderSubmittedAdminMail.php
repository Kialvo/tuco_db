<?php

namespace App\Mail;

use App\Models\Order;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class OrderSubmittedAdminMail extends Mailable
{
    use Queueable, SerializesModels;

    public string $mailer = 'auth';

    public function __construct(public Order $order) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'LINK IN A BLINK — New Order Received',
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'emails.order-submitted-admin',
        );
    }
}
