<?php

namespace App\Models;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Country extends Model
{
    use HasFactory;

    protected $fillable = [
        'country_name',
    ];

    /**
     * If you want to relate back to your websites (domains)
     * via a one-to-many relationship:
     */
    public function websites()
    {
        return $this->hasMany(Website::class);
    }
}
