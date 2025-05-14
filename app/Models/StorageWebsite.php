<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Relations\Pivot;

class StorageWebsite extends Pivot
{
    protected $table = 'storage_website';
    public $incrementing = false;
    protected $fillable = [
        'storage_id',
        'domain_name',
        'website_id',
        // 'some_extra_field', // example if you had additional pivot data
    ];




}
