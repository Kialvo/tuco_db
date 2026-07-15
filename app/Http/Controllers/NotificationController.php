<?php

namespace App\Http\Controllers;

use App\Models\CrmNotification;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

/**
 * Tuco's own notification bell (internal staff only). Reads the org-wide hub
 * table scoped to source_app='tuco' + the logged-in user's email — mirroring
 * how the SOPs bell shows only SOPs items. The Menford CRM remains the one
 * place that shows everything (for people whose email exists in crm_users).
 */
class NotificationController extends Controller
{
    /** Bell is for internal staff (admin/editor) — never marketplace guests. */
    private function guard(Request $request): string
    {
        $user = $request->user();
        abort_unless($user && in_array($user->role, ['admin', 'editor'], true), 403);

        return mb_strtolower(trim($user->email));
    }

    private function scoped(Request $request)
    {
        return CrmNotification::query()->fromTuco()->forEmail($this->guard($request));
    }

    /*======================================================================
    | GET /notifications            → list (unread first, newest first)
    | GET /notifications?unread=1   → unread count only
    ======================================================================*/
    public function index(Request $request)
    {
        if ($request->query('unread') === '1') {
            // ?unread=1&entityType=campaign → per-entity unread map for the
            // 💬 bubbles (mirrors the CRM's getUnreadByEntityType).
            if ($entityType = $request->query('entityType')) {
                $map = $this->scoped($request)->unread()
                    ->where('entity_type', $entityType)
                    ->whereNotNull('entity_id')
                    ->selectRaw('entity_id, COUNT(*) as cnt')
                    ->groupBy('entity_id')
                    ->pluck('cnt', 'entity_id');

                return response()->json(['unread' => $map]);
            }

            return response()->json(['count' => $this->scoped($request)->unread()->count()]);
        }

        // Sender photos: the shared hub table has no photo column for tuco
        // rows (the CRM computes photos by joining crm_users), so we resolve
        // cosmetically by matching from_user_name against staff avatars.
        $avatars = \App\Models\User::whereIn('role', ['admin', 'editor'])
            ->whereNotNull('avatar_url')
            ->get(['name', 'avatar_url'])
            ->keyBy('name');

        $rows = $this->scoped($request)
            ->orderByRaw('read_at IS NOT NULL ASC')
            ->orderByDesc('created_at')
            ->limit(30)
            ->get()
            ->map(fn (CrmNotification $n) => [
                'id'              => $n->id,
                'type'            => $n->type,
                'source_app'      => $n->source_app,
                'entity_label'    => $n->entity_label,
                'body'            => $n->body,
                'link'            => $n->link,
                'from_user_name'  => $n->from_user_name,
                'from_user_photo' => $avatars->get($n->from_user_name)?->avatar,
                'read_at'         => $n->read_at ? Carbon::parse($n->read_at)->toIso8601String() : null,
                'created_at'      => Carbon::parse($n->created_at)->toIso8601String(),
            ]);

        return response()->json(['notifications' => $rows]);
    }

    /*======================================================================
    | PATCH /notifications/read   {id} or {all:true}
    ======================================================================*/
    public function markRead(Request $request)
    {
        $data = $request->validate([
            'id'          => 'nullable|integer',
            'all'         => 'nullable|boolean',
            'entity_type' => 'nullable|string|max:30',
            'entity_id'   => 'nullable|string|max:255',
        ]);

        $query = $this->scoped($request)->unread();

        if (! empty($data['all'])) {
            $query->update(['read_at' => now()]);
        } elseif (! empty($data['id'])) {
            $query->where('id', $data['id'])->update(['read_at' => now()]);
        } elseif (! empty($data['entity_type']) && ! empty($data['entity_id'])) {
            // Whole-thread read (opening a 💬 modal) — CRM's markReadByEntity.
            $query->where('entity_type', $data['entity_type'])
                ->where('entity_id', $data['entity_id'])
                ->update(['read_at' => now()]);
        } else {
            return response()->json(['error' => 'Pass id, all, or entity_type+entity_id'], 400);
        }

        return response()->json(['status' => 'success']);
    }

    /*======================================================================
    | DELETE /notifications/{id}   → dismiss one
    | DELETE /notifications?all=1  → clear own tuco feed
    ======================================================================*/
    public function destroy(Request $request, int $id)
    {
        $this->scoped($request)->where('id', $id)->delete();

        return response()->json(['status' => 'success']);
    }

    public function clearAll(Request $request)
    {
        abort_unless($request->query('all') === '1', 400, 'Pass ?all=1 to clear all');

        $this->scoped($request)->delete();

        return response()->json(['status' => 'success']);
    }
}
