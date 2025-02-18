<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\Pivot;

class CategoryWebsite extends Pivot
{
    protected $table = 'category_website';

    // If you have extra columns on the pivot table (besides website_id and category_id),
    // you can list them here in $fillable or $guarded.
    protected $fillable = [
        'website_id',
        'category_id',
        // 'some_extra_field', // example if you had additional pivot data
    ];

    // If your pivot has timestamps, you can set:
    // public $timestamps = true;
}
