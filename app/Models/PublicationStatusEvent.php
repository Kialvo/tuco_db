<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * One recorded status change on a publication.
 *
 * Append-only history: UPDATED_AT is disabled because these rows are facts
 * about a moment, never edited. This is what lets the customer-facing order
 * tracker put a date beside each completed step.
 */
class PublicationStatusEvent extends Model
{
    public const UPDATED_AT = null;

    protected $fillable = ['storage_id', 'status', 'changed_by'];

    public function storage(): BelongsTo
    {
        return $this->belongsTo(Storage::class);
    }
}
