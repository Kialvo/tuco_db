<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OrderItem extends Model
{
    use HasFactory;

    public const TYPE_STANDARD = 'standard';

    public const TYPE_SENSITIVE = 'sensitive';

    protected $fillable = [
        'order_id',
        'website_id',
        'storage_id',
        'article_type',
        'unit_price',
        'tokens_held',
        'held_at',
        'captured_at',
        'released_at',
        'release_reason',
    ];

    protected $casts = [
        'unit_price' => 'decimal:2',
        'tokens_held' => 'integer',
        'held_at' => 'datetime',
        'captured_at' => 'datetime',
        'released_at' => 'datetime',
    ];

    /** Why a hold was given back. */
    public const RELEASE_PUBLISHER_REFUSED = 'publisher_refused';

    public const RELEASE_PUBLISHER_DISAPPEARED = 'publisher_disappeared';

    public const RELEASE_CANCELLED = 'cancelled';

    public const RELEASE_ADMIN = 'admin';

    /** Tokens are committed and not yet resolved either way. */
    public function isHeld(): bool
    {
        return $this->held_at !== null
            && $this->captured_at === null
            && $this->released_at === null;
    }

    /** Published and paid for: the hold became revenue. */
    public function isCaptured(): bool
    {
        return $this->captured_at !== null;
    }

    /** The site fell through and the tokens went back. */
    public function isReleased(): bool
    {
        return $this->released_at !== null;
    }

    /**
     * What this item costs in tokens. 1 token = EUR 1 and client prices are
     * whole euros, so the price IS the token count — but round rather than
     * cast, so a stray 526.999999 can never silently undercharge by a token.
     */
    public function tokenCost(): int
    {
        return (int) round((float) $this->unit_price);
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function website(): BelongsTo
    {
        return $this->belongsTo(Website::class);
    }

    /** The publication fulfilling this item — null for orders placed before the tracker. */
    public function publication(): BelongsTo
    {
        return $this->belongsTo(Storage::class, 'storage_id');
    }

    /**
     * Customer-facing tracker for this item: the five approved steps, each with
     * the date it was reached, plus a red flag if something went wrong.
     *
     * Pass the parent order when one is already in hand (the order page has it)
     * to keep this from re-querying it once per row.
     */
    public function progress(?Order $order = null): array
    {
        $timestamps = [];

        // "Order Submitted" is a fact of the order, not of the publication, so
        // it holds even for orders placed before publications were opened
        // automatically — those show step 1 done and the rest pending.
        $submittedAt = ($order ?? $this->order)?->submitted_at;
        if ($submittedAt) {
            $timestamps['order_submitted'] = \App\Support\DisplayTime::format($submittedAt);
        }

        $publication = $this->publication;

        if (! $publication) {
            return \App\Support\PublicationProgress::build(null, $timestamps);
        }

        // Earliest event per customer step — the moment it was first reached.
        foreach ($publication->statusEvents as $event) {
            $key = \App\Support\PublicationProgress::stepKeyFor($event->status);
            if ($key !== null && ! isset($timestamps[$key])) {
                $timestamps[$key] = \App\Support\DisplayTime::format($event->created_at);
            }
        }

        return \App\Support\PublicationProgress::build($publication->status, $timestamps);
    }

    /**
     * Snap the unit_price to the website's current price for the chosen article_type.
     */
    public function refreshPrice(): void
    {
        if (! $this->website) {
            return;
        }

        $this->unit_price = $this->article_type === self::TYPE_SENSITIVE
            ? ($this->website->sensitive_topic_price ?? $this->website->price ?? 0)
            : ($this->website->price ?? 0);
    }
}
