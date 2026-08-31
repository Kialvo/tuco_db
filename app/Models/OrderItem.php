<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OrderItem extends Model
{
    use HasFactory;

    public const TYPE_STANDARD  = 'standard';
    public const TYPE_SENSITIVE = 'sensitive';

    protected $fillable = [
        'order_id',
        'website_id',
        'storage_id',
        'article_type',
        'unit_price',
    ];

    protected $casts = [
        'unit_price' => 'decimal:2',
    ];

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
        if (! $this->website) return;

        $this->unit_price = $this->article_type === self::TYPE_SENSITIVE
            ? ($this->website->sensitive_topic_price ?? $this->website->price ?? 0)
            : ($this->website->price ?? 0);
    }
}
