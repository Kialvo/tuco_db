<?php

namespace App\Mail;

use App\Models\OrderItem;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Carbon;

/**
 * The warning shot before an article is published without the client's reply.
 *
 * Auto-approval is what stops an order — and the tokens held against it —
 * hanging on somebody's silence. But publishing something a client never read,
 * with no warning, is exactly what becomes "you published without my
 * permission". This email is what makes the deadline defensible: it names the
 * exact date, and says how to ask for more time.
 */
class ArticleApprovalReminderMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public OrderItem $item,
        public Carbon $deadline,
    ) {
        $this->mailer = 'orders';
    }

    public function envelope(): Envelope
    {
        $domain = $this->item->website?->domain_name ?? 'your placement';

        return new Envelope(
            subject: "Action needed: your article for {$domain} publishes on {$this->deadline->format('j F')}",
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'emails.article-approval-reminder',
        );
    }
}
