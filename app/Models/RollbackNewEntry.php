<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RollbackNewEntry extends Model
{

    protected $table = 'rollback_new_entries';

    protected $fillable = ['token', 'new_entry_id', 'snapshot'];

    protected $casts = [
        'snapshot' => 'array',
    ];
}
