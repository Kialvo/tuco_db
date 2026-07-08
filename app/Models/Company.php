<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Company extends Model
{
    use HasFactory;

    protected $fillable = ['name'];

    public function clients()
    {
        return $this->hasMany(Client::class);
    }

    /* Link Building CRM — campaigns for this company (additive; reads lb_campaigns) */
    public function campaigns()
    {
        return $this->hasMany(Campaign::class);
    }
}
