<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Copy extends Model
{
    use HasFactory;
    protected $table = 'copy_tbl';
    protected $fillable = ['copy_val'];

    public function storages()  { return $this->hasMany(Storage::class); }
    public function websites()  { return $this->hasMany(Website::class); }
}

