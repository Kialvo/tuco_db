<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Contact extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'email',
        'phone',
        'facebook',
        'instagram'

    ];

    // If you want the reverse relationship:
    public function websites()
    {
        return $this->hasMany(Website::class);
    }
}

