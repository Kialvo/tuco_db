<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Contact extends Model
{
    use HasFactory,SoftDeletes;

    protected $fillable = [
        'name',
        'email',
        'phone',
        'facebook',
        'instagram',
'deleted_at'
    ];

    // If you want the reverse relationship:
    public function websites()
    {
        return $this->hasMany(Website::class);
    }
}

