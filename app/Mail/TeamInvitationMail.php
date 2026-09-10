<?php

namespace App\Mail;

use App\Models\MarketplaceTeamInvitation;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/**
 * The invitation to share a team's token balance.
 *
 * The link carries the token, which is the proof the owner initiated this.
 * Redeeming it still requires signing in as the invited address, so a
 * forwarded email cannot let a third party into somebody's wallet.
 */
class TeamInvitationMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public MarketplaceTeamInvitation $invitation)
    {
        $this->mailer = 'orders';
    }

    public function envelope(): Envelope
    {
        $team = $this->invitation->team?->name ?? 'a team';

        return new Envelope(
            subject: "You've been invited to join {$team} on Link in a Blink",
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'emails.team-invitation',
            with: [
                'url' => route('billing.team.accept', ['token' => $this->invitation->token]),
                'team' => $this->invitation->team,
                'inviter' => $this->invitation->inviter,
                'expiresAt' => $this->invitation->expires_at,
            ],
        );
    }
}
