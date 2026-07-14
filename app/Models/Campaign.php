<?php

namespace App\Models;

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
        'offer_ready_date'     => 'date',
        'deadline'             => 'date',
        'completion_date'      => 'date',
        'next_update_date'     => 'date',
        'deal_value'           => 'decimal:2',
        'target_value'         => 'decimal:2',
        'live_count'           => 'decimal:2',
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
        $live   = (float) $this->live_count;
        $isBudget = $this->target_type === 'budget';

        if ($target <= 0) {
            return ['has' => false, 'pct' => 0, 'label' => '—', 'missing' => '—', 'tone' => 'gray'];
        }

        $pct        = (int) min(100, round($live / $target * 100));
        $missingVal = $target - $live;
        $tone       = $pct >= 100 ? 'green' : ($pct >= 60 ? 'amber' : 'red');

        if ($isBudget) {
            $label   = '€' . number_format($live, 0) . ' / €' . number_format($target, 0);
            $missing = $missingVal <= 0
                ? 'Target reached'
                : '€' . number_format($missingVal, 0) . ' missing';
        } else {
            $label   = (int) $live . ' / ' . (int) $target . ' pubs';
            $missing = $missingVal <= 0
                ? 'Target reached'
                : (int) $missingVal . ' pub' . ($missingVal != 1 ? 's' : '') . ' missing';
        }

        return ['has' => true, 'pct' => $pct, 'label' => $label, 'missing' => $missing, 'tone' => $tone];
    }
}
