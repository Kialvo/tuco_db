<?php

namespace App\Http\Controllers\Admin;

use App\Exceptions\InsufficientTokens;
use App\Http\Controllers\Controller;
use App\Models\MarketplaceTeam;
use App\Models\TokenTransaction;
use App\Services\Tokens\TokenLedger;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * Manual token adjustments: goodwill credit, or clawing back tokens granted
 * in error.
 *
 * Admin-only, and every row it writes carries the acting admin and a written
 * reason — an adjustment nobody can explain six months later is worse than no
 * adjustment at all.
 *
 * It cannot repair a stuck HOLD; that is `tokens:fix-hold`, which goes through
 * the hold state machine. This only moves the spendable balance.
 */
class TokenAdjustmentController extends Controller
{
    public function __construct(private readonly TokenLedger $ledger) {}

    public function index(Request $request)
    {
        $search = trim((string) $request->query('q', ''));

        $teams = MarketplaceTeam::query()
            ->with(['owner', 'tokenAccount'])
            ->withCount('members')
            ->when($search !== '', function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhereHas('owner', fn ($o) => $o->where('email', 'like', "%{$search}%")
                        ->orWhere('name', 'like', "%{$search}%"));
            })
            ->orderBy('name')
            ->paginate(25)
            ->withQueryString();

        return view('admin.tokens.index', [
            'teams' => $teams,
            'search' => $search,
            // Regenerated per render: a double-clicked submit reuses this and
            // books the adjustment once. See TokenLedger::adjust().
            'formToken' => (string) Str::uuid(),
            'recent' => TokenTransaction::with('account.team')
                ->where('type', TokenTransaction::TYPE_ADJUSTMENT)
                ->latest('id')
                ->limit(20)
                ->get(),
        ]);
    }

    public function store(Request $request, MarketplaceTeam $team)
    {
        $data = $request->validate([
            'direction' => ['required', 'in:credit,debit'],
            'tokens' => ['required', 'integer', 'min:1', 'max:100000'],
            'reason' => ['required', 'string', 'min:5', 'max:255'],
            'form_token' => ['required', 'string', 'max:64'],
        ], [
            'reason.min' => 'Please write a real reason — this is what explains the row later.',
            'tokens.min' => 'An adjustment has to move at least one token.',
        ]);

        $actor = $request->user();
        $signed = $data['direction'] === 'credit' ? $data['tokens'] : -$data['tokens'];

        // The account belongs to the team; accountFor() resolves through the
        // owner so a team that has never transacted still gets a wallet.
        $owner = $team->owner;

        if (! $owner) {
            return back()->withErrors(['tokens' => 'That team has no owner, so it has no wallet.']);
        }

        $account = $this->ledger->accountFor($owner);

        try {
            $this->ledger->adjust(
                $account,
                $signed,
                $data['reason'],
                'adjustment:'.$data['form_token'],
                $actor,
            );
        } catch (InsufficientTokens $e) {
            return back()->withErrors([
                'tokens' => "That would take the balance below zero — it holds {$e->balance} tokens "
                    ."and you tried to remove {$data['tokens']}.",
            ]);
        } catch (\InvalidArgumentException $e) {
            return back()->withErrors(['tokens' => $e->getMessage()]);
        }

        Log::warning("[tokens] MANUAL ADJUSTMENT {$signed} on team {$team->id} ({$team->name}) "
            ."by {$actor->email}: {$data['reason']}");

        $verb = $signed > 0 ? 'Credited' : 'Removed';

        return back()->with('status',
            "{$verb} ".abs($signed)." tokens on {$team->name}. Balance is now "
            .$this->ledger->accountFor($owner)->fresh()->balance_cached.'.');
    }
}
