<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Reply under an lb_update (table: lb_update_replies). Author = tuco user.
 */
class LbUpdateReply extends Model
{
    use SoftDeletes;

    protected $table = 'lb_update_replies';

    protected $fillable = ['lb_update_id', 'user_id', 'body', 'edited_at'];

    protected $casts = ['edited_at' => 'datetime'];

    // Named parentUpdate — `update()` would collide with Model::update().
    public function parentUpdate()
    {
        return $this->belongsTo(LbUpdate::class, 'lb_update_id');
    }

    public function author()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
