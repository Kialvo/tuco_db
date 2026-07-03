<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * Link Building CRM per-publication comment (table: lb_publication_comments).
 * Author = Linkinablink user.
 */
class PublicationComment extends Model
{
    use HasFactory;

    protected $table = 'lb_publication_comments';

    protected $fillable = [
        'lb_publication_id',
        'user_id',
        'body',
    ];

    public function publication()
    {
        return $this->belongsTo(Publication::class, 'lb_publication_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
