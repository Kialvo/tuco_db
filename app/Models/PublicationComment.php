<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * Link Building CRM per-publication comment (table: lb_publication_comments).
 * Phase 3: attached to the storage row (the publication). Author = Linkinablink user.
 */
class PublicationComment extends Model
{
    use HasFactory;

    protected $table = 'lb_publication_comments';

    protected $fillable = [
        'storage_id',
        'user_id',
        'body',
    ];

    public function storage()
    {
        return $this->belongsTo(Storage::class, 'storage_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
