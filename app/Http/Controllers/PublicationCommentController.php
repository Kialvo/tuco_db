<?php

namespace App\Http\Controllers;

use App\Models\PublicationComment;
use App\Models\Storage;
use Illuminate\Http\Request;

/**
 * Phase 3 — comments hang off the storage row (the publication).
 * Route parameter stays {storage}; URLs keep the /publications prefix.
 */
class PublicationCommentController extends Controller
{
    /*======================================================================
    |  INDEX – comments for a publication (JSON, oldest first)
    ======================================================================*/
    public function index(Storage $storage)
    {
        $comments = $storage->publicationComments()
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
    public function store(Request $request, Storage $storage)
    {
        $data = $request->validate([
            'body' => 'required|string|max:5000',
        ]);

        $comment = $storage->publicationComments()->create([
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
            'count'   => $storage->publicationComments()->count(),
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

        $storageId = $comment->storage_id;
        $comment->delete();

        return response()->json([
            'status' => 'success',
            'count'  => PublicationComment::where('storage_id', $storageId)->count(),
        ]);
    }
}
