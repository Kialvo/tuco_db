<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class OutreachLog extends Model
{
    protected $fillable = [
        'website_id','contact_id','to_email','subject','body','target_url',
        'status','error','sent_by','sent_at',
    ];
    public $timestamps = false;

    protected $casts = [
        'sent_at' => 'datetime',
    ];

    public function website(){ return $this->belongsTo(Website::class); }
    public function contact(){ return $this->belongsTo(Contact::class); }
}
