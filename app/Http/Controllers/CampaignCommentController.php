<?php

namespace App\Http\Controllers;

use App\Models\Campaign;
use App\Models\CampaignComment;
use Illuminate\Http\Request;

class CampaignCommentController extends Controller
{
    /*======================================================================
    |  INDEX – comments for a campaign (JSON, oldest first)
    ======================================================================*/
    public function index(Campaign $campaign)
    {
        $comments = $campaign->comments()
            ->with('user:id,name')
            ->orderBy('created_at')
            ->get()
            ->map(fn (CampaignComment $c) => [
                'id'     => $c->id,
                'author' => $c->user?->name ?? 'Unknown',
                'body'   => $c->body,
                'date'   => $c->created_at?->format('d/m/Y'),
                'own'    => $c->user_id === auth()->id(),
            ]);

        return response()->json(['data' => $comments]);
    }

    /*======================================================================
    |  STORE
    ======================================================================*/
    public function store(Request $request, Campaign $campaign)
    {
        $data = $request->validate([
            'body' => 'required|string|max:5000',
        ]);

        $comment = $campaign->comments()->create([
            'user_id' => auth()->id(),
            'body'    => $data['body'],
        ]);

        $comment->load('user:id,name');

        return response()->json([
            'status'  => 'success',
            'comment' => [
                'id'     => $comment->id,
                'author' => $comment->user?->name ?? 'Unknown',
                'body'   => $comment->body,
                'date'   => $comment->created_at?->format('d/m/Y'),
                'own'    => true,
            ],
            'count'   => $campaign->comments()->count(),
        ]);
    }

    /*======================================================================
    |  DESTROY – author or admin only
    ======================================================================*/
    public function destroy(CampaignComment $comment)
    {
        if ($comment->user_id !== auth()->id() && ! auth()->user()->isAdmin()) {
            abort(403);
        }

        $campaignId = $comment->lb_campaign_id;
        $comment->delete();

        return response()->json([
            'status' => 'success',
            'count'  => CampaignComment::where('lb_campaign_id', $campaignId)->count(),
        ]);
    }
}
