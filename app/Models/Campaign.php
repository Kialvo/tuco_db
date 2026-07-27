<?php

namespace App\Models;

use App\Support\PublicationStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Link Building CRM campaign (table: lb_campaigns).
 * Separate from the shared Menford `campaigns` table.
 */
class Campaign extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'lb_campaigns';

    protected $fillable = [
        'code',
        'company_id',
        'contact_id',
        'responsible_user_id',
        'service',
        'status',
        'deal_value',
        'target_type',
        'target_value',
        'live_count',
        'budget_approval_date',
        'offer_ready_date',
        'deadline',
        'completion_date',
        'next_update_date',
    ];

    protected $casts = [
        'budget_approval_date' => 'date',
        'offer_ready_date' => 'date',
        'deadline' => 'date',
        'completion_date' => 'date',
        'next_update_date' => 'date',
        'deal_value' => 'decimal:2',
        'target_value' => 'decimal:2',
        'live_count' => 'decimal:2',
    ];

    /* ---------------------------------------------------------------- Events */

    protected static function booted(): void
    {
        // live_count's UNIT depends on target_type (€ sum vs pub count).
        // Storage events keep it fresh on publication changes; this keeps it
        // fresh when the campaign itself switches unit (edit modal) — the
        // gap that froze a € sum under a "pubs" label (campaign #6).
        static::saved(function (Campaign $c) {
            if ($c->wasChanged('target_type')) {
                $c->recomputeProgress();   // saveQuietly() inside → no event loop
            }
        });
    }

    /* ---------------------------------------------------------------- Relations */

    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    public function contact()
    {
        return $this->belongsTo(Client::class, 'contact_id');
    }

    public function responsibleUser()
    {
        return $this->belongsTo(User::class, 'responsible_user_id');
    }

    /**
     * Publications of this campaign = storage rows linked via storage.lb_campaign_id
     * (Phase 3: the storage row IS the publication — single source of truth).
     */
    public function publications()
    {
        return $this->hasMany(Storage::class, 'lb_campaign_id');
    }

    public function comments()
    {
        return $this->hasMany(CampaignComment::class, 'lb_campaign_id');
    }

    /**
     * Automatic completion date: for a "Completed*" campaign, the most recent
     * LIVE DATE (storage.publication_date) across its publications; null otherwise.
     * Derived on read (never stored) — supersedes the old manual completion_date.
     */
    public function liveCompletionDate(): ?\Carbon\Carbon
    {
        $completed = config('linkbuilding.campaign_statuses.Completed', []);
        if (! in_array($this->status, $completed, true)) {
            return null;
        }

        // Prefer the withMax aggregate when the query supplied it (list / stats)
        // to avoid an N+1; otherwise fall back to the eager-loaded relation (show).
        $max = array_key_exists('latest_live_date', $this->attributes)
            ? $this->latest_live_date
            : $this->publications->max('publication_date');

        return $max ? \Carbon\Carbon::parse($max) : null;
    }

    /**
     * Recompute the target's "first number" (live_count) from published publications.
     * Budget target => sum of their total_revenues; otherwise count of published.
     * Called automatically on any linked Storage save/delete/restore (Storage::booted()).
     */
    public function recomputeProgress(): void
    {
        $this->live_count = $this->target_type === 'budget'
            ? (float) $this->publications()->where('status', 'article_published')->sum('total_revenues')
            : $this->publications()->where('status', 'article_published')->count();

        $this->saveQuietly();
    }

    /* ---------------------------------------------------------------- Config helpers */

    /** Grouped statuses from config. */
    public static function statusGroups(): array
    {
        return config('linkbuilding.campaign_statuses', []);
    }

    /** Flat list of all valid campaign statuses (for validation). */
    public static function allStatuses(): array
    {
        return collect(static::statusGroups())->flatten()->values()->all();
    }

    /* ---------------------------------------------------------------- Accessors */

    /**
     * Progress toward the target (ports the mockup's tgt()).
     * Returns pct, human label, "missing" text, and a tone keyword.
     */
    public function getProgressAttribute(): array
    {
        $target = (float) $this->target_value;
        $live = (float) $this->live_count;
        $isBudget = $this->target_type === 'budget';

        if ($target <= 0) {
            return ['has' => false, 'pct' => 0, 'label' => '—', 'missing' => '—', 'tone' => 'gray'];
        }

        $pct = (int) min(100, round($live / $target * 100));
        $missingVal = $target - $live;
        $tone = $pct >= 100 ? 'green' : ($pct >= 60 ? 'amber' : 'red');

        if ($isBudget) {
            $label = '€'.number_format($live, 0).' / €'.number_format($target, 0);
            $missing = $missingVal <= 0
                ? 'Target reached'
                : '€'.number_format($missingVal, 0).' missing';
        } else {
            $label = (int) $live.' / '.(int) $target.' pubs';
            $missing = $missingVal <= 0
                ? 'Target reached'
                : (int) $missingVal.' pub'.($missingVal != 1 ? 's' : '').' missing';
        }

        return ['has' => true, 'pct' => $pct, 'label' => $label, 'missing' => $missing, 'tone' => $tone];
    }

    /**
     * Target bar segments: published (green) + in flight (yellow) + the grey
     * remainder that is still missing. Replaces the old percentage-driven tone
     * (green ≥100 / amber ≥60 / red <60), which said how far along a campaign
     * was but hid WHERE the work sat.
     *
     * The unit follows target_type: € of total_revenues for a budget target,
     * publication count otherwise — the same split recomputeProgress() applies
     * to live_count.
     *
     * `done` reads live_count rather than a fresh aggregate on purpose: it is
     * the very number the cell prints beside the bar ("4 / 10"), and Storage
     * events keep it current. Deriving the green segment from anything else
     * could make the bar contradict the number next to it.
     *
     * The in-flight figure prefers the query-supplied aggregates
     * (inflight_revenue / inflight_count) to avoid an N+1 on the list, and
     * falls back to the eager-loaded relation on the show page — the same
     * pattern as getFinancialsAttribute() and liveCompletionDate().
     */
    public function progressSegments(): array
    {
        $isBudget = $this->target_type === 'budget';
        $target = (float) $this->target_value;
        $done = (float) $this->live_count;

        if ($isBudget) {
            $inflight = array_key_exists('inflight_revenue', $this->attributes)
                ? (float) $this->inflight_revenue
                : (float) $this->publications->whereIn('status', PublicationStatus::inFlightSlugs())->sum('total_revenues');
        } else {
            $inflight = array_key_exists('inflight_count', $this->attributes)
                ? (float) $this->inflight_count
                : (float) $this->publications->whereIn('status', PublicationStatus::inFlightSlugs())->count();
        }

        if ($target <= 0) {
            return [
                'has' => false, 'done' => $done, 'inflight' => $inflight, 'target' => 0.0,
                'donePct' => 0.0, 'inflightPct' => 0.0, 'label' => '—', 'missing' => '—', 'tone' => 'gray',
            ];
        }

        // Widths keep one decimal: rounding both segments to int leaves a
        // visible grey sliver when done + inflight exactly covers the target.
        $donePct = round(min(100, $done / $target * 100), 1);
        $inflightPct = round(min(100 - $donePct, $inflight / $target * 100), 1);

        // Bare amount ("€2,800" / "3") for the two-part caption, which has to
        // fit a table cell; the single-part caption keeps the fuller wording.
        $amt = fn (float $v) => $isBudget ? '€'.number_format($v, 0) : (string) (int) $v;

        $remainder = max(0, $target - $done - $inflight);

        if ($done >= $target) {
            $missing = 'Target reached';
            $tone = 'green';
        } elseif ($inflight > 0) {
            // When what is in flight already covers the gap the second clause
            // would read "€0 missing" — drop it rather than print a dead zero.
            $shortfall = $isBudget ? round($remainder) : (int) $remainder;
            $missing = $amt($inflight).' in flight'
                .($shortfall > 0 ? ' · '.$amt($remainder).' missing' : '');
            $tone = 'amber';
        } else {
            $missing = $isBudget
                ? '€'.number_format($remainder, 0).' missing'
                : (int) $remainder.' pub'.((int) $remainder !== 1 ? 's' : '').' missing';
            $tone = 'gray';
        }

        $label = $isBudget
            ? '€'.number_format($done, 0).' / €'.number_format($target, 0)
            : (int) $done.' / '.(int) $target.' pubs';

        return [
            'has' => true,
            'done' => $done,
            'inflight' => $inflight,
            'target' => $target,
            'donePct' => $donePct,
            'inflightPct' => $inflightPct,
            'label' => $label,
            'missing' => $missing,
            'tone' => $tone,
            // Spoken equivalent of the bar's three segments — the widths alone
            // carry no meaning to a screen reader.
            'aria' => $amt($done).' published, '.$amt($inflight).' in flight, target '.$amt($target)
                .($isBudget ? ' euro' : ' publications'),
        ];
    }

    /**
     * Financials over PUBLISHED publications (status = article_published):
     * revenue = SUM total_revenues, cost = SUM total_cost,
     * profit  = revenue − cost, pct = profit / revenue × 100 (null when no revenue).
     *
     * Prefers the query-supplied withSum aggregates (pub_revenue / pub_cost) to
     * avoid an N+1 on the list; falls back to the eager-loaded publications
     * collection on the show page — same pattern as liveCompletionDate().
     */
    public function getFinancialsAttribute(): array
    {
        $revenue = array_key_exists('pub_revenue', $this->attributes)
            ? (float) $this->pub_revenue
            : (float) $this->publications->where('status', 'article_published')->sum('total_revenues');

        $cost = array_key_exists('pub_cost', $this->attributes)
            ? (float) $this->pub_cost
            : (float) $this->publications->where('status', 'article_published')->sum('total_cost');

        $profit = $revenue - $cost;

        return [
            'revenue' => $revenue,
            'cost' => $cost,
            'profit' => $profit,
            'pct' => $revenue > 0 ? round($profit / $revenue * 100, 1) : null,
        ];
    }
}
