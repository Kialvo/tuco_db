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
