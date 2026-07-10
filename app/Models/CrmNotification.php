<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

/**
 * Org-wide notification hub row — the SHARED `notifications` table owned by
 * the Menford CRM (menford-crm repo, lib/notifications-db.ts). Tuco reads and
 * writes rows directly (same MySQL DB) but NEVER migrates this table.
 *
 * Identity model: `recipient_email` is the universal cross-app key; `user_id`
 * points at `crm_users` and is resolved from the email when a match exists
 * (rows with NULL user_id are valid — they just don't surface in the CRM's
 * central bell). Tuco rows always carry source_app='tuco'.
 */
class CrmNotification extends Model
{
    public const SOURCE = 'tuco';

    protected $table = 'notifications';

    /** created_at has a DB default (CURRENT_TIMESTAMP); no updated_at column. */
    public $timestamps = false;

    protected $fillable = [
        'user_id',
        'recipient_email',
        'type',
        'source_app',
        'entity_type',
        'entity_id',
        'entity_label',
        'body',
        'link',
        'from_user_id',
        'from_user_name',
        'update_id',
        'reply_id',
        'read_at',
    ];

    /* ---------------------------------------------------------------- Scopes */

    /** Only rows created by this app — every tuco read/update MUST use this. */
    public function scopeFromTuco(Builder $q): Builder
    {
        return $q->where('source_app', self::SOURCE);
    }

    public function scopeForEmail(Builder $q, string $email): Builder
    {
        return $q->where('recipient_email', mb_strtolower(trim($email)));
    }

    public function scopeUnread(Builder $q): Builder
    {
        return $q->whereNull('read_at');
    }
}
