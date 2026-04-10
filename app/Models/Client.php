<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Client extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'first_name',
        'last_name',
        'email',
        'company_id',
    ];

    /* -------------------------------- Relationships ---------------------*/
    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    public function storages()
    {
        return $this->hasMany(Storage::class);
    }

    public function websites()  {

        return $this->hasMany(Website::class);
    }
}

