<?php

namespace App\Http\Controllers;

use App\Models\Publication;
use App\Models\PublicationComment;
use Illuminate\Http\Request;

class PublicationCommentController extends Controller
{
    /*======================================================================
    |  INDEX – comments for a publication (JSON, oldest first)
    ======================================================================*/
    public function index(Publication $publication)
    {
        $comments = $publication->comments()
            ->with('user:id,name')
            ->orderBy('created_at')
            ->get()
            ->map(fn (PublicationComment $c) => [
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
    public function store(Request $request, Publication $publication)
    {
        $data = $request->validate([
            'body' => 'required|string|max:5000',
        ]);

        $comment = $publication->comments()->create([
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
            'count'   => $publication->comments()->count(),
        ]);
    }

    /*======================================================================
    |  DESTROY – author or admin only
    ======================================================================*/
    public function destroy(PublicationComment $comment)
    {
        if ($comment->user_id !== auth()->id() && ! auth()->user()->isAdmin()) {
            abort(403);
        }

        $publicationId = $comment->lb_publication_id;
        $comment->delete();

        return response()->json([
            'status' => 'success',
            'count'  => PublicationComment::where('lb_publication_id', $publicationId)->count(),
        ]);
    }
}
