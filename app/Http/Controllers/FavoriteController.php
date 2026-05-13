<?php

namespace App\Http\Controllers;

use App\Models\Website;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class FavoriteController extends Controller
{
    public function toggle(Request $request, Website $website)
    {
        $user = $request->user();
        if (! $user || ! $user->isGuest()) {
            abort(403);
        }

        $exists = DB::table('user_favorite_domains')
            ->where('user_id', $user->id)
            ->where('website_id', $website->id)
            ->exists();

        if ($exists) {
            DB::table('user_favorite_domains')
                ->where('user_id', $user->id)
                ->where('website_id', $website->id)
                ->delete();
            $favorite = false;
        } else {
            $snapshot = $this->buildWebsiteSnapshot($website);
            DB::table('user_favorite_domains')->insert([
                'user_id' => $user->id,
                'website_id' => $website->id,
                'website_snapshot' => json_encode($snapshot, JSON_UNESCAPED_UNICODE),
                'created_at' => now(),
                'updated_at' => now(),
            ]);
            $favorite = true;
        }

        return response()->json([
            'status' => 'ok',
            'favorite' => $favorite,
        ]);
    }


    /**
     * Bulk-toggle favorites for a set of website IDs (currently visible page).
     * action=add → add any that aren't already favorited.
     * action=remove → remove any that ARE favorited.
     */
    public function bulk(Request $request)
    {
        $user = $request->user();
        if (! $user || ! $user->isGuest()) {
            abort(403);
        }

        $validated = $request->validate([
            'ids'    => 'required|array|min:1|max:500',
            'ids.*'  => 'integer',
            'action' => 'required|in:add,remove',
        ]);

        $ids = array_values(array_unique(array_map('intval', $validated['ids'])));

        if ($validated['action'] === 'remove') {
            DB::table('user_favorite_domains')
                ->where('user_id', $user->id)
                ->whereIn('website_id', $ids)
                ->delete();
        } else {
            // Insert only the websites that aren't already favorited
            $existing = DB::table('user_favorite_domains')
                ->where('user_id', $user->id)
                ->whereIn('website_id', $ids)
                ->pluck('website_id')
                ->all();

            $toInsert = array_diff($ids, $existing);
            if (! empty($toInsert)) {
                $websites = Website::with(['country', 'language', 'contact', 'categories'])
                    ->whereIn('id', $toInsert)
                    ->get();
                $now = now();
                $rows = $websites->map(fn ($w) => [
                    'user_id'          => $user->id,
                    'website_id'       => $w->id,
                    'website_snapshot' => json_encode($this->buildWebsiteSnapshot($w), JSON_UNESCAPED_UNICODE),
                    'created_at'       => $now,
                    'updated_at'       => $now,
                ])->all();
                if (! empty($rows)) {
                    DB::table('user_favorite_domains')->insert($rows);
                }
            }
        }

        // Return the updated favorite-set for the requested IDs so the UI can repaint
        $favoritedNow = DB::table('user_favorite_domains')
            ->where('user_id', $user->id)
            ->whereIn('website_id', $ids)
            ->pluck('website_id')
            ->all();

        return response()->json([
            'status'    => 'ok',
            'favorited' => array_values(array_map('intval', $favoritedNow)),
        ]);
    }

    private function buildWebsiteSnapshot(Website $website): array
    {
        $website->loadMissing(['country', 'language', 'contact', 'categories']);

        return [
            'attributes' => $website->getAttributes(),
            'country_name' => optional($website->country)->country_name,
            'language_name' => optional($website->language)->name,
            'contact_name' => optional($website->contact)->name,
            'categories' => $website->categories->pluck('name')->all(),
        ];
    }

}
