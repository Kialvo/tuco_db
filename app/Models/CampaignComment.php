<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * Link Building CRM per-campaign comment (table: lb_campaign_comments).
 * Author = Linkinablink user.
 */
class CampaignComment extends Model
{
    use HasFactory;

    protected $table = 'lb_campaign_comments';

    protected $fillable = [
        'lb_campaign_id',
        'user_id',
        'body',
    ];

    public function campaign()
    {
        return $this->belongsTo(Campaign::class, 'lb_campaign_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
