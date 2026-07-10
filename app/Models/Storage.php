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
        'lb_campaign_id',
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
        'website',   // legacy free-text publisher domain (used when no websites match)
    ];

    /* --------------------------------------------------------------------
     | Model events — keep the linked campaign's progress in sync
     |--------------------------------------------------------------------*/
    protected static function booted(): void
    {
        // NOTE: in the `saved` event getOriginal() still returns pre-save
        // values (syncOriginal runs after), so we can catch re-links too.
        static::saved(function (Storage $s) {
            $ids = array_unique(array_filter([
                $s->lb_campaign_id,
                $s->getOriginal('lb_campaign_id'),
            ]));
            foreach ($ids as $id) {
                Campaign::withTrashed()->find($id)?->recomputeProgress();
            }

            // Notify the campaign responsible when a linked publication
            // transitions TO Article Published (never the actor themself).
            if ($s->lb_campaign_id
                && $s->wasChanged('status')
                && $s->status === 'article_published') {
                $campaign    = Campaign::with('responsibleUser')->find($s->lb_campaign_id);
                $responsible = $campaign?->responsibleUser;
                if ($responsible) {
                    \App\Services\NotificationHub::notify([
                        'type'           => 'publication_published',
                        'recipients'     => [$responsible],
                        'exclude'        => auth()->user(),
                        'entity_type'    => 'publication',
                        'entity_id'      => (string) $s->id,
                        'entity_label'   => trim($campaign->code . ' — ' . ($s->publisher_domain ?? '#' . $s->id)),
                        'body'           => 'Publication went live (Article Published)',
                        'link'           => route('crm.campaigns.show', $campaign->id) . '?pubthread=' . $s->id,
                        'from_user_name' => auth()->user()?->name,
                    ]);
                }
            }
        });

        $recompute = function (Storage $s) {
            if ($s->lb_campaign_id) {
                Campaign::withTrashed()->find($s->lb_campaign_id)?->recomputeProgress();
            }
        };
        static::deleted($recompute);
        static::restored($recompute);
    }

    /* --------------------------------------------------------------------
     | Relationships
     |--------------------------------------------------------------------*/
    public function site()
    {
        return $this->belongsTo(Website::class, 'website_id');
    }

    /**
     * Link Building CRM campaign this row is a publication of.
     * Named lbCampaign because `campaign` is already a COLUMN (Target Domain).
     * withTrashed so rows linked to a soft-deleted campaign still display.
     */
    public function lbCampaign()
    {
        return $this->belongsTo(Campaign::class, 'lb_campaign_id')->withTrashed();
    }

    public function publicationComments()
    {
        return $this->hasMany(PublicationComment::class, 'storage_id');
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

    /* --------------------------------------------------------------------
     | Accessors — unified publication status + display helpers
     |--------------------------------------------------------------------*/

    /** Human label for the status slug (null for empty/legacy '0'). */
    public function getStatusLabelAttribute(): ?string
    {
        return \App\Support\PublicationStatus::label($this->status);
    }

    /** 1 = Site Evaluation, 2 = Production (derived from the slug). */
    public function getStatusGroupAttribute(): int
    {
        return \App\Support\PublicationStatus::group($this->status);
    }

    /** Publisher domain shown in the Campaigns view (website relation, legacy fallbacks). */
    public function getPublisherDomainAttribute(): ?string
    {
        return $this->site?->domain_name
            ?: ($this->attributes['website'] ?? null)
            ?: ($this->attributes['domain_name'] ?? null);
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

        // 3) Website default contact  ✅
        return $this->site?->contact ?? null;
    }

}
