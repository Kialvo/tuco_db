<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Order extends Model
{
    use HasFactory;

    public const STATUS_DRAFT       = 'draft';
    public const STATUS_SUBMITTED   = 'submitted';
    public const STATUS_IN_PROGRESS = 'in_progress';
    public const STATUS_COMPLETED   = 'completed';
    public const STATUS_CANCELLED   = 'cancelled';

    public const STATUSES = [
        self::STATUS_DRAFT,
        self::STATUS_SUBMITTED,
        self::STATUS_IN_PROGRESS,
        self::STATUS_COMPLETED,
        self::STATUS_CANCELLED,
    ];

    public const STATUS_LABELS = [
        self::STATUS_DRAFT       => 'Draft',
        self::STATUS_SUBMITTED   => 'Submitted',
        self::STATUS_IN_PROGRESS => 'In Progress',
        self::STATUS_COMPLETED   => 'Completed',
        self::STATUS_CANCELLED   => 'Cancelled',
    ];

    /** Tone keys consumable by the <x-ds.pill> component */
    public const STATUS_TONES = [
        self::STATUS_DRAFT       => 'gray',
        self::STATUS_SUBMITTED   => 'amber',
        self::STATUS_IN_PROGRESS => 'purple',
        self::STATUS_COMPLETED   => 'green',
        self::STATUS_CANCELLED   => 'red',
    ];

    protected $fillable = [
        'user_id',
        'status',
        'notes',
        'submitted_at',
        'status_changed_at',
    ];

    protected $casts = [
        'submitted_at'     => 'datetime',
        'status_changed_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    public function getTotalAmountAttribute(): float
    {
        return (float) $this->items->sum('unit_price');
    }

    public function getItemCountAttribute(): int
    {
        return $this->items->count();
    }

    public function getStatusLabelAttribute(): string
    {
        return self::STATUS_LABELS[$this->status] ?? ucfirst($this->status);
    }

    public function getStatusToneAttribute(): string
    {
        return self::STATUS_TONES[$this->status] ?? 'gray';
    }

    public function getReferenceAttribute(): string
    {
        return '#' . str_pad((string) $this->id, 4, '0', STR_PAD_LEFT);
    }

    public function scopeDraft($query)
    {
        return $query->where('status', self::STATUS_DRAFT);
    }

    public function scopeSubmitted($query)
    {
        return $query->whereNotIn('status', [self::STATUS_DRAFT, self::STATUS_CANCELLED]);
    }

    public function scopeForUser($query, int $userId)
    {
        return $query->where('user_id', $userId);
    }
}
