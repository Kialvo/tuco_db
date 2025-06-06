<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Website extends Model
{
    use HasFactory,SoftDeletes;

    // If you want to allow mass assignment on certain fields:
    protected $fillable = [
        'domain_name',
        'status',
        'country_id',
        'contact_id',
        'currency_code',
        'language_id',
        'publisher_price',
        'date_publisher_price',
        'link_insertion_price',
        'no_follow_price',
        'special_topic_price',
        'profit',
        'linkbuilder',
        'automatic_evaluation',
        'kialvo_evaluation',
        'date_kialvo_evaluation',
        'type_of_website',
        'DA',
        'PA',
        'TF',
        'CF',
        'DR',
        'UR',
        'ZA',
        'as_metric',
        'seozoom',
        'TF_vs_CF',
        'semrush_traffic',
        'ahrefs_keyword',
        'ahrefs_traffic',
        'keyword_vs_traffic',
        'seo_metrics_date',
        'betting',
        'trading',
        'more_than_one_link',
        'copywriting',
        'no_sponsored_tag',
        'social_media_sharing',
        'post_in_homepage',
        'extra_notes',
        'original_publisher_price',
        'original_no_follow_price',
        'original_link_insertion_price',
        'original_special_topic_price',
        'permanent_link'
    ];

    // RELATIONSHIPS:

    public function contact()
    {
        return $this->belongsTo(Contact::class);
    }

    public function currency()
    {
        return $this->belongsTo(Currency::class);
    }

    public function language()
    {
        return $this->belongsTo(Language::class);
    }

    public function country()
    {
        return $this->belongsTo(Country::class);
    }

    public function storages()
    {
        return $this->hasMany(Storage::class, 'website_id');
    }

    public function categories()
    {
        // Many-to-many
        return $this->belongsToMany(Category::class, 'category_website')
        ->using(CategoryWebsite::class);
    }
}
