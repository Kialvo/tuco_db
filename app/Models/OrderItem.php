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
