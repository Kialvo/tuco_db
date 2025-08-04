<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Category extends Model
{
    use HasFactory;

    protected $fillable = ['name'];

    public function websites()
    {
        return $this->belongsToMany(Website::class, 'category_website')
            ->using(CategoryWebsite::class);
    }

    public function new_entries()
    {
        return $this->belongsToMany(NewEntry::class, 'category_new_entry')
            ->using(CategoryNewEntry::class);
    }

    public function storages()
    {
        return $this->belongsToMany(Storage::class, 'category_storage')
            ->using(CategoryStorage::class);
    }
}
