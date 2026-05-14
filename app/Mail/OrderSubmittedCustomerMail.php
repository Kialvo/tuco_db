<?php

namespace App\Mail;

use App\Models\Order;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class OrderSubmittedCustomerMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public Order $order)
    {
        $this->mailer = 'orders';
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: "Order {$this->order->reference} received — Linkinablink",
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'emails.order-submitted-customer',
        );
    }
}
