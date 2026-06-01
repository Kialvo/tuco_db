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
        'phone',
        'company_id',
        'first_contact_date',
        'job_title',
        'primary_language_id',
        'channel_1',
        'channel_2',
        'channel_3',
        'whatsapp',
        'telegram',
        'facebook',
        'discord',
        'linkedin',
        'location_address',
        'location_lat',
        'location_lng',
        'location_country_id',
        'birthday',
        'country_of_origin_id',
        'religion',
    ];

    protected $casts = [
        'first_contact_date' => 'date',
        'birthday'           => 'date',
    ];

    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    public function primaryLanguage()
    {
        return $this->belongsTo(Language::class, 'primary_language_id');
    }

    public function originCountry()
    {
        return $this->belongsTo(Country::class, 'country_of_origin_id');
    }

    public function locationCountry()
    {
        return $this->belongsTo(Country::class, 'location_country_id');
    }

    public function storages()
    {
        return $this->hasMany(Storage::class);
    }

    public function websites()
    {
        return $this->hasMany(Website::class, 'contact_id');
    }
}
