<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class NewEntry extends Model
{
    use HasFactory,SoftDeletes;

    // If you want to allow mass assignment on certain fields:
    protected $fillable = [
        // identifiers / f-keys
        'domain_name','status','country_id','language_id','contact_id',
        'currency_code','type_of_website','linkbuilder',

        // price columns *****  ← the ones now missing
        'publisher_price','link_insertion_price','no_follow_price','special_topic_price',
        'original_publisher_price','original_link_insertion_price',
        'original_no_follow_price','original_special_topic_price',

        // calculated
        'profit','automatic_evaluation','kialvo_evaluation','TF_vs_CF','keyword_vs_traffic',

        // seo numbers
        'DA','PA','TF','CF','DR','UR','ZA','as_metric','seozoom',
        'semrush_traffic','ahrefs_keyword','ahrefs_traffic',

        // dates
        'date_publisher_price','date_kialvo_evaluation','seo_metrics_date','first_contact_date',

        // flags & notes
        'betting','trading','permanent_link','more_than_one_link',
        'copywriting','no_sponsored_tag','social_media_sharing','post_in_homepage',
        'extra_notes',

        'banner_price','sitewide_link_price',
        'original_banner_price','original_sitewide_link_price','copied_to_overview'
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
        return $this->belongsToMany(Category::class, 'category_new_entry')
            ->using(CategoryNewEntry::class)
             ->withTimestamps();
    }
}
