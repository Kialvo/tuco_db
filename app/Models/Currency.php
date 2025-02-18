<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Currency extends Model
{
    use HasFactory;

    protected $fillable = [
        'code',
        'symbol',
        'name',
    ];

    // If needed, here's the relationship:
    public function websites()
    {
        return $this->hasMany(Website::class);
    }
}
