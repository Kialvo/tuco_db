<?php

namespace App\Http\Controllers;

use App\Models\Campaign;
use App\Models\LbUpdate;
use App\Models\LbUpdateReply;
use App\Models\Storage;
use App\Models\User;
use App\Services\NotificationHub;
use App\Support\Mentions;
use Illuminate\Http\Request;

/**
 * CRM-style conversations (updates + replies) on campaigns and publications.
 * Mirrors menford-crm's lib/conversations-db.ts rules:
 *  - new update  → notify @mentions (+ tuco extra: the campaign responsible)
 *  - reply       → notify @mentions + the parent update's author
 *                  ("reply_to_your_update") unless they replied or were mentioned
 *  - edit (own)  → notify only NEWLY-added mentions, set edited_at
 *  - delete (own)→ soft delete
 */
class ConversationController extends Controller
{
    public const TYPES = ['campaign', 'publication'];

    /*======================================================================
    | Guards & helpers
    ======================================================================*/
    private function guard(Request $request): User
    {
        $user = $request->user();
        abort_unless($user && in_array($user->role, ['admin', 'editor'], true), 403);

        return $user;
    }

    /**
     * Resolve entity meta: [Campaign|null, label, deep-link, responsible User|null].
     */
    private function entityMeta(string $type, string $id): array
    {
        if ($type === 'campaign') {
            $campaign = Campaign::with('responsibleUser')->find($id);
            abort_unless((bool) $campaign, 404);

            return [
                'label'       => $campaign->code,
                'link'        => route('crm.campaigns.index') . '?thread=' . $campaign->id,
                'responsible' => $campaign->responsibleUser,
            ];
        }

        // publication → storage row
        $storage  = Storage::with('site:id,domain_name')->find($id);
        abort_unless((bool) $storage, 404);
        $campaign = $storage->lbCampaign()->with('responsibleUser')->first();

        return [
            'label'       => trim(($campaign?->code ?? '') . ' — ' . ($storage->publisher_domain ?? '#' . $storage->id), ' —'),
            'link'        => $campaign
                ? route('crm.campaigns.show', $campaign->id) . '?pubthread=' . $storage->id
                : route('storages.edit', $storage->id),
            'responsible' => $campaign?->responsibleUser,
        ];
    }

    private function serializeAuthor(?User $u): array
    {
        return ['id' => $u?->id, 'name' => $u?->name ?? 'Unknown', 'avatar' => $u?->avatar];
    }

    private function serializeReply(LbUpdateReply $r, int $meId): array
    {
        return [
            'id'         => $r->id,
            'update_id'  => $r->lb_update_id,
            'author'     => $this->serializeAuthor($r->author),
            'own'        => $r->user_id === $meId,
            'body'       => $r->body,
            'created_at' => $r->created_at?->toIso8601String(),
            'edited_at'  => $r->edited_at?->toIso8601String(),
        ];
    }

    private function serializeUpdate(LbUpdate $u, int $meId): array
    {
        return [
            'id'         => $u->id,
            'author'     => $this->serializeAuthor($u->author),
            'own'        => $u->user_id === $meId,
            'body'       => $u->body,
            'created_at' => $u->created_at?->toIso8601String(),
            'edited_at'  => $u->edited_at?->toIso8601String(),
            'replies'    => $u->replies->map(fn ($r) => $this->serializeReply($r, $meId))->values(),
        ];
    }

    /** Staff users mentioned in a body (never the author). */
    private function mentionedUsers(string $body, User $author)
    {
        return User::whereIn('id', Mentions::extractUserIds($body))
            ->whereIn('role', ['admin', 'editor'])
            ->whereNot('id', $author->id)
            ->get();
    }

    /*======================================================================
    | GET conversations/{type}/{id} — full thread
    ======================================================================*/
    public function show(Request $request, string $type, string $id)
    {
        $me = $this->guard($request);
        abort_unless(in_array($type, self::TYPES, true), 404);

        $updates = LbUpdate::forEntity($type, $id)
            ->with(['author:id,name,avatar_url', 'replies' => fn ($q) => $q->with('author:id,name,avatar_url')->orderBy('created_at')])
            ->orderBy('created_at')
            ->get();

        return response()->json([
            'updates' => $updates->map(fn ($u) => $this->serializeUpdate($u, $me->id))->values(),
        ]);
    }

    /*======================================================================
    | POST conversations/{type}/{id} — new update
    ======================================================================*/
    public function store(Request $request, string $type, string $id)
    {
        $me = $this->guard($request);
        abort_unless(in_array($type, self::TYPES, true), 404);
        $data = $request->validate(['body' => 'required|string|max:5000']);
        $meta = $this->entityMeta($type, $id);

        $update = LbUpdate::create([
            'entity_type' => $type,
            'entity_id'   => (string) $id,
            'user_id'     => $me->id,
            'body'        => $data['body'],
        ]);

        $mentioned = $this->mentionedUsers($data['body'], $me);
        NotificationHub::notify([
            'type'           => 'mention',
            'recipients'     => $mentioned,
            'exclude'        => $me,
            'entity_type'    => $type,
            'entity_id'      => (string) $id,
            'entity_label'   => $meta['label'],
            'body'           => 'mentioned you on ' . $meta['label'],
            'link'           => $meta['link'],
            'from_user_name' => $me->name,
        ]);

        // Tuco extra vs the CRM: the campaign responsible always hears about
        // new updates (unless they wrote it or were already mentioned).
        if ($meta['responsible'] && ! $mentioned->contains('id', $meta['responsible']->id)) {
            NotificationHub::notify([
                'type'           => 'comment',
                'recipients'     => [$meta['responsible']],
                'exclude'        => $me,
                'entity_type'    => $type,
                'entity_id'      => (string) $id,
                'entity_label'   => $meta['label'],
                'body'           => 'commented on ' . $meta['label'],
                'link'           => $meta['link'],
                'from_user_name' => $me->name,
            ]);
        }

        $update->load('author:id,name,avatar_url');

        return response()->json(['status' => 'success', 'update' => $this->serializeUpdate($update, $me->id)]);
    }

    /*======================================================================
    | POST conversations/updates/{update}/replies — new reply
    ======================================================================*/
    public function storeReply(Request $request, LbUpdate $update)
    {
        $me   = $this->guard($request);
        $data = $request->validate(['body' => 'required|string|max:5000']);
        $meta = $this->entityMeta($update->entity_type, $update->entity_id);

        $reply = LbUpdateReply::create([
            'lb_update_id' => $update->id,
            'user_id'      => $me->id,
            'body'         => $data['body'],
        ]);

        $mentioned = $this->mentionedUsers($data['body'], $me);
        NotificationHub::notify([
            'type'           => 'mention',
            'recipients'     => $mentioned,
            'exclude'        => $me,
            'entity_type'    => $update->entity_type,
            'entity_id'      => $update->entity_id,
            'entity_label'   => $meta['label'],
            'body'           => 'mentioned you on ' . $meta['label'],
            'link'           => $meta['link'],
            'from_user_name' => $me->name,
        ]);

        // CRM parity: tell the update's author someone replied — unless they
        // replied themselves or were already @mentioned in this reply.
        if ($update->user_id !== $me->id && ! $mentioned->contains('id', $update->user_id)) {
            NotificationHub::notify([
                'type'           => 'reply_to_your_update',
                'recipients'     => [$update->author],
                'exclude'        => $me,
                'entity_type'    => $update->entity_type,
                'entity_id'      => $update->entity_id,
                'entity_label'   => $meta['label'],
                'body'           => 'replied to your update',
                'link'           => $meta['link'],
                'from_user_name' => $me->name,
            ]);
        }

        $reply->load('author:id,name,avatar_url');

        return response()->json(['status' => 'success', 'reply' => $this->serializeReply($reply, $me->id)]);
    }

    /*======================================================================
    | PATCH — edit own update / reply (notify only NEWLY added mentions)
    ======================================================================*/
    public function updateUpdate(Request $request, LbUpdate $update)
    {
        $me = $this->guard($request);
        abort_unless($update->user_id === $me->id, 403);
        $data = $request->validate(['body' => 'required|string|max:5000']);

        $this->notifyNewMentions($update->body, $data['body'], $me, $update->entity_type, $update->entity_id);
        $update->update(['body' => $data['body'], 'edited_at' => now()]);

        return response()->json(['status' => 'success']);
    }

    public function updateReply(Request $request, LbUpdateReply $reply)
    {
        $me = $this->guard($request);
        abort_unless($reply->user_id === $me->id, 403);
        $data = $request->validate(['body' => 'required|string|max:5000']);

        $parent = $reply->parentUpdate;
        $this->notifyNewMentions($reply->body, $data['body'], $me, $parent->entity_type, $parent->entity_id);
        $reply->update(['body' => $data['body'], 'edited_at' => now()]);

        return response()->json(['status' => 'success']);
    }

    private function notifyNewMentions(string $oldBody, string $newBody, User $me, string $type, string $id): void
    {
        $oldIds = Mentions::extractUserIds($oldBody);
        $added  = array_diff(Mentions::extractUserIds($newBody), $oldIds);
        if (! $added) {
            return;
        }

        $meta = $this->entityMeta($type, $id);
        NotificationHub::notify([
            'type'           => 'mention',
            'recipients'     => User::whereIn('id', $added)->whereIn('role', ['admin', 'editor'])->get(),
            'exclude'        => $me,
            'entity_type'    => $type,
            'entity_id'      => (string) $id,
            'entity_label'   => $meta['label'],
            'body'           => 'mentioned you on ' . $meta['label'],
            'link'           => $meta['link'],
            'from_user_name' => $me->name,
        ]);
    }

    /*======================================================================
    | DELETE — soft delete own update / reply
    ======================================================================*/
    public function destroyUpdate(Request $request, LbUpdate $update)
    {
        $me = $this->guard($request);
        abort_unless($update->user_id === $me->id, 403);
        $update->delete();

        return response()->json(['status' => 'success']);
    }

    public function destroyReply(Request $request, LbUpdateReply $reply)
    {
        $me = $this->guard($request);
        abort_unless($reply->user_id === $me->id, 403);
        $reply->delete();

        return response()->json(['status' => 'success']);
    }

    /*======================================================================
    | GET conversations/counts/{type} — total messages per entity
    | (updates + replies, like the CRM's getMessageCountsByEntity)
    ======================================================================*/
    public function counts(Request $request, string $type)
    {
        $this->guard($request);
        abort_unless(in_array($type, self::TYPES, true), 404);

        $updateCounts = LbUpdate::where('entity_type', $type)
            ->selectRaw('entity_id, COUNT(*) as cnt')->groupBy('entity_id')->pluck('cnt', 'entity_id');

        $replyCounts = LbUpdateReply::query()
            ->join('lb_updates', 'lb_updates.id', '=', 'lb_update_replies.lb_update_id')
            ->where('lb_updates.entity_type', $type)
            ->whereNull('lb_updates.deleted_at')
            ->whereNull('lb_update_replies.deleted_at')
            ->selectRaw('lb_updates.entity_id, COUNT(lb_update_replies.id) as cnt')
            ->groupBy('lb_updates.entity_id')
            ->pluck('cnt', 'entity_id');

        $totals = [];
        foreach ($updateCounts as $eid => $cnt) {
            $totals[$eid] = (int) $cnt;
        }
        foreach ($replyCounts as $eid => $cnt) {
            $totals[$eid] = ($totals[$eid] ?? 0) + (int) $cnt;
        }

        return response()->json(['counts' => $totals]);
    }
}
