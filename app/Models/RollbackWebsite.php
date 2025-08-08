<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RollbackWebsite extends Model
{
    protected $fillable = ['token', 'website_id', 'snapshot'];

    protected $casts = [
        'snapshot' => 'array',
    ];
}
