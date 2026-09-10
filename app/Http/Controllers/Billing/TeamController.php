<?php

namespace App\Http\Controllers\Billing;

use App\Http\Controllers\Controller;
use App\Mail\TeamInvitationMail;
use App\Models\MarketplaceTeamInvitation;
use App\Models\User;
use App\Services\Tokens\TeamMembership;
use App\Services\Tokens\TokenLedger;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

/**
 * Managing who shares a balance.
 *
 * Every owner action goes through TeamMembership, which is where the rules
 * live — this only turns a RuntimeException from there into a message on the
 * page. Nothing here decides who may do what, so the HTTP layer and any future
 * caller cannot disagree about it.
 *
 * ⚠️ Every route name added here must also be listed in
 * RestrictGuestToDomainsMiddleware::ALLOWED_ROUTE_NAMES, or guests are
 * silently redirected to /websites and the page simply appears not to work.
 */
class TeamController extends Controller
{
    public function __construct(
        private readonly TeamMembership $teams,
        private readonly TokenLedger $ledger,
    ) {}

    public function show(Request $request)
    {
        $user = $request->user();
        $team = $this->teams->teamFor($user);
        $account = $this->ledger->accountFor($user);

        return view('billing.team', [
            'team' => $team->load('owner'),
            'members' => $team->members()->orderBy('name')->get(),
            'invitations' => $team->pendingInvitations()->latest()->get(),
            'isOwner' => $team->isOwnedBy($user),
            'balance' => (int) $account->balance_cached,
            'held' => $this->ledger->heldTotal($account),
        ]);
    }

    public function invite(Request $request)
    {
        $data = $request->validate([
            'email' => ['required', 'email', 'max:255'],
        ], [
            'email.email' => 'That does not look like an email address.',
        ]);

        $user = $request->user();
        $team = $this->teams->teamFor($user);

        if (mb_strtolower($data['email']) === mb_strtolower((string) $user->email)) {
            return back()->withErrors(['email' => 'You are already on this team.']);
        }

        try {
            $invitation = $this->teams->invite($team, $user, $data['email']);
        } catch (\RuntimeException $e) {
            return back()->withErrors(['email' => $e->getMessage()]);
        }

        try {
            Mail::to($invitation->email)->send(new TeamInvitationMail($invitation));
        } catch (\Throwable $e) {
            // The invitation exists either way; only the email failed. Say so
            // plainly rather than pretending it was sent, so they can chase it.
            Log::error('[teams] invitation email failed for '.$invitation->email.': '.$e->getMessage());

            return back()->with('status',
                "Invitation created for {$invitation->email}, but the email could not be sent. "
                .'Please contact support so we can resend it.');
        }

        return back()->with('status', "Invitation sent to {$invitation->email}.");
    }

    /**
     * Redeem an invitation.
     *
     * Behind auth, so the visitor has proved who they are before the address
     * on the invitation is checked against them — the token alone is never
     * enough to join somebody's team.
     */
    public function accept(Request $request, string $token)
    {
        $invitation = MarketplaceTeamInvitation::where('token', $token)->first();

        if (! $invitation) {
            return redirect()->route('billing.team.show')
                ->withErrors(['team' => 'That invitation link is not valid.']);
        }

        try {
            $team = $this->teams->accept($invitation, $request->user());
        } catch (\RuntimeException $e) {
            return redirect()->route('billing.team.show')->withErrors(['team' => $e->getMessage()]);
        }

        return redirect()->route('billing.team.show')
            ->with('status', "You have joined {$team->name}. You can now spend the shared balance.");
    }

    public function removeMember(Request $request, User $member)
    {
        $user = $request->user();
        $team = $this->teams->teamFor($user);

        try {
            $this->teams->remove($team, $user, $member);
        } catch (\RuntimeException $e) {
            return back()->withErrors(['team' => $e->getMessage()]);
        }

        return back()->with('status', "{$member->name} no longer has access to this balance.");
    }

    public function transferOwnership(Request $request)
    {
        $data = $request->validate(['user_id' => ['required', 'integer']]);

        $user = $request->user();
        $team = $this->teams->teamFor($user);
        $newOwner = User::find($data['user_id']);

        if (! $newOwner) {
            return back()->withErrors(['team' => 'That person could not be found.']);
        }

        try {
            $this->teams->transferOwnership($team, $user, $newOwner);
        } catch (\RuntimeException $e) {
            return back()->withErrors(['team' => $e->getMessage()]);
        }

        return back()->with('status', "{$newOwner->name} now owns this team.");
    }

    public function revokeInvitation(Request $request, MarketplaceTeamInvitation $invitation)
    {
        $user = $request->user();
        $team = $this->teams->teamFor($user);

        try {
            $this->teams->revokeInvitation($team, $user, $invitation);
        } catch (\RuntimeException $e) {
            return back()->withErrors(['team' => $e->getMessage()]);
        }

        return back()->with('status', 'Invitation cancelled.');
    }
}
