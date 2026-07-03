<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Link Building CRM publication (table: lb_publications).
 * One row per publisher/site within a campaign.
 */
class Publication extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'lb_publications';

    protected $fillable = [
        'lb_campaign_id',
        'site',
        'status',
        'status_group',
        'price',
        'live_url',
        'live_date',
        'date_to_copywriter',
        'date_from_copywriter',
        'date_to_blog',
        'notes',
    ];

    protected $casts = [
        'live_date'            => 'date',
        'date_to_copywriter'   => 'date',
        'date_from_copywriter' => 'date',
        'date_to_blog'         => 'date',
        'price'                => 'decimal:2',
        'status_group'         => 'integer',
    ];

    protected static function booted(): void
    {
        // Keep status_group in sync with status (2 = Production, else 1)
        static::saving(function (Publication $p) {
            $p->status_group = in_array($p->status, static::productionStatuses(), true) ? 2 : 1;
        });

        // Keep the parent campaign's progress (live_count) in sync with Published pubs
        static::saved(fn (Publication $p)    => optional($p->campaign)->recomputeProgress());
        static::deleted(fn (Publication $p)  => optional($p->campaign)->recomputeProgress());
        static::restored(fn (Publication $p) => optional($p->campaign)->recomputeProgress());
    }

    /* ---------------------------------------------------------------- Relations */

    public function campaign()
    {
        return $this->belongsTo(Campaign::class, 'lb_campaign_id');
    }

    public function comments()
    {
        return $this->hasMany(PublicationComment::class, 'lb_publication_id');
    }

    /* ---------------------------------------------------------------- Config helpers */

    public static function statusGroupsConfig(): array
    {
        return config('linkbuilding.publication_statuses', []);
    }

    public static function allStatuses(): array
    {
        return collect(static::statusGroupsConfig())->flatten()->values()->all();
    }

    public static function productionStatuses(): array
    {
        return config('linkbuilding.production_statuses', []);
    }
}
