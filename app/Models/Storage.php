<?php
// Laravel Eloquent models for the “storage” domain — **no casts**, only the
// bare logic + relationships you asked for.
// ─────────────────────────────────────────────────────────────────────────────

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\Pivot;
use Illuminate\Database\Eloquent\SoftDeletes;

/* ==========================================================================
 | STORAGE
 *-------------------------------------------------------------------------*/
class Storage extends Model
{
    use HasFactory, SoftDeletes;

    /**
     * Explicit table name (Laravel would default to `storages`).
     */
    protected $table = 'storage';

    /**
     * All DB columns that can be mass‑assigned.
     */
    protected $fillable = [
        'website_id',
        'status',
        'LB',
        'client_id',
        'copy_id',
        'copy_nr',
        'copywriter_commision_date',
        'copywriter_submission_date',
        'copywriter_period',
        'language_id',
        'country_id',
        'publisher_amount',
        'publisher_currency',
        'publisher',
        'total_cost',
        'menford',
        'client_copy',
        'total_revenues',
        'profit',
        'campaign',
        'anchor_text',
        'target_url',
        'campaign_code',
        'article_sent_to_publisher',
        'publication_date',
        'expiration_date',
        'publisher_period',
        'article_url',
        'method_payment_to_us',
        'invoice_menford',
        'invoice_menford_nr',
        'invoice_company',
        'payment_to_us_date',
        'bill_publisher_date',
        'bill_publisher_name',
        'bill_publisher_nr',
        'payment_to_publisher_date',
        'method_payment_to_publisher',
        'files',
    ];

    /* --------------------------------------------------------------------
     | Relationships
     |--------------------------------------------------------------------*/
    public function site()
    {
        return $this->belongsTo(Website::class, 'website_id');
    }


    public function client()
    {
        return $this->belongsTo(Client::class);
    }

    public function copy()
    {
        return $this->belongsTo(Copy::class); // copy_tbl
    }

    public function language()
    {
        return $this->belongsTo(Language::class);
    }

    public function country()
    {
        return $this->belongsTo(Country::class);
    }
    public function contacts()
    {
        return $this->belongsToMany(Contact::class, 'contact_storage')
            ->withPivot(['is_primary', 'role'])
            ->withTimestamps();
    }
    public function categories()
    {
        // Many‑to‑many (pivot: category_storage)
        return $this->belongsToMany(Category::class, 'category_storage')
            ->using(CategoryStorage::class)->withTimestamps();
    }

    /**
     * Handy accessor to always get "the" primary contact for this storage.
     * Priority:
     *  1. contact marked is_primary via pivot
     *  2. if only 1 contact is attached, use that
     *  3. fallback to the website->contact
     */
    public function getPrimaryContactAttribute(): ?Contact
    {
        // 1) Pivot primary
        $primary = $this->contacts->firstWhere('pivot.is_primary', true);
        if ($primary) {
            return $primary;
        }

        // 2) Only one contact attached
        if ($this->contacts->count() === 1) {
            return $this->contacts->first();
        }

        // 3) Website default contact
        return $this->website?->contact ?? null;
    }
}
