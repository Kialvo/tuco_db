<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * CRM-style conversation update (table: lb_updates). One row = one top-level
 * comment on an entity ('campaign' → lb_campaigns.id, 'publication' →
 * storage.id). Replies hang off lb_update_replies. Author = tuco user.
 */
class LbUpdate extends Model
{
    use SoftDeletes;

    protected $table = 'lb_updates';

    protected $fillable = ['entity_type', 'entity_id', 'user_id', 'body', 'edited_at'];

    protected $casts = ['edited_at' => 'datetime'];

    public function author()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function replies()
    {
        return $this->hasMany(LbUpdateReply::class, 'lb_update_id');
    }

    public function scopeForEntity(Builder $q, string $type, string|int $id): Builder
    {
        return $q->where('entity_type', $type)->where('entity_id', (string) $id);
    }
}
