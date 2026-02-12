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

    public function exportCsv(Request $request)
    {
        $user = $request->user();
        if (! $user || ! $user->isGuest()) {
            abort(403);
        }

        $websites = $this->favoriteWebsitesFor($user);
        $this->maskGuestFields($websites);

        $csvData = [];
        $csvData[] = [
            'ID',
            'Domain',
            'Publisher Price',
            'Kialvo',
            'Profit',
            'Banner Price',
            'Site-wide Link Price',
            'DA',
            'Country',
            'Language',
            'Contact',
            'Categories',
            'Status',
            'Currency',
            'Date Publisher Price',
            'Link Insertion Price',
            'No Follow Price',
            'Special Topic Price',
            'Linkbuilder',
            'Automatic Evaluation',
            'Date Kialvo Evaluation',
            'Type of Website',
            'PA',
            'TF',
            'CF',
            'DR',
            'UR',
            'ZA',
            'AS',
            'SEO Zoom',
            'TF vs CF',
            'Semrush Traffic',
            'Ahrefs Keyword',
            'Ahrefs Traffic',
            'Keyword vs Traffic',
            'SEO Metrics Date',
            'Betting',
            'Trading',
            'LINK LIFETIME',
            'More than 1 link',
            'Copywriting',
            'No Sponsored Tag',
            'Social Media Sharing',
            'Post in Homepage',
            'Date Added',
            'Notes',
            'Internal Notes',
        ];

        foreach ($websites as $web) {
            $csvData[] = [
                $web->id,
                $web->domain_name,
                $web->publisher_price,
                $web->kialvo_evaluation,
                $web->profit,
                $web->banner_price,
                $web->sitewide_link_price,
                $web->DA,
                optional($web->country)->country_name,
                optional($web->language)->name,
                optional($web->contact)->name,
                $web->categories->pluck('name')->join(', '),
                $web->status,
                $web->currency_code,
                $web->date_publisher_price,
                $web->link_insertion_price,
                $web->no_follow_price,
                $web->special_topic_price,
                $web->linkbuilder,
                $web->automatic_evaluation,
                $web->date_kialvo_evaluation,
                $web->type_of_website,
                $web->PA,
                $web->TF,
                $web->CF,
                $web->DR,
                $web->UR,
                $web->ZA,
                $web->as_metric,
                $web->seozoom,
                $web->TF_vs_CF,
                $web->semrush_traffic,
                $web->ahrefs_keyword,
                $web->ahrefs_traffic,
                $web->keyword_vs_traffic,
                $web->seo_metrics_date,
                $this->yesNo($web->betting),
                $this->yesNo($web->trading),
                $this->yesNo($web->permanent_link),
                $this->yesNo($web->more_than_one_link),
                $this->yesNo($web->copywriting),
                $this->yesNo($web->no_sponsored_tag),
                $this->yesNo($web->social_media_sharing),
                $this->yesNo($web->post_in_homepage),
                $web->created_at,
                $web->notes,
                $web->extra_notes,
            ];
        }

        $filename = 'user_'.$user->id.'_favorites_'.date('Y-m-d_His').'.csv';
        $handle   = fopen('php://temp', 'r+');
        // UTF-8 BOM for Excel/Numbers compatibility
        fwrite($handle, "\xEF\xBB\xBF");
        foreach ($csvData as $row) {
            fputcsv($handle, $row, ';');
        }
        rewind($handle);
        $csvOutput = stream_get_contents($handle);
        fclose($handle);

        return response($csvOutput, 200, [
            'Content-Type'        => 'text/csv; charset=UTF-8',
            'Content-Disposition' => "attachment; filename=\"$filename\"",
        ]);
    }

    public function exportPdf(Request $request)
    {
        $user = $request->user();
        if (! $user || ! $user->isGuest()) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        $websites = $this->favoriteWebsitesFor($user);
        $this->maskGuestFields($websites);

        $html = view('websites.favorites_pdf', compact('user', 'websites'))->render();
        $pdf = \PDF::loadHTML($html)->setPaper('a1', 'landscape');

        return $pdf->download('user_'.$user->id.'_favorites_'.date('Y-m-d_His').'.pdf');
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

    private function favoriteWebsitesFor($user)
    {
        return Website::query()
            ->select('websites.*', 'ufd.website_snapshot')
            ->join('user_favorite_domains as ufd', function ($join) use ($user) {
                $join->on('ufd.website_id', '=', 'websites.id')
                    ->where('ufd.user_id', '=', $user->id);
            })
            ->where(function ($query) {
                $query->whereNull('websites.status')
                    ->orWhereRaw('LOWER(websites.status) <> ?', ['past']);
            })
            ->with(['country', 'language', 'contact', 'categories'])
            ->get();
    }

    private function maskGuestFields($websites): void
    {
        foreach ($websites as $web) {
            $web->status = null;
            $web->currency_code = null;
            $web->publisher_price = null;
            $web->no_follow_price = null;
            $web->special_topic_price = null;
            $web->link_insertion_price = null;
            $web->banner_price = null;
            $web->sitewide_link_price = null;
            $web->profit = null;
            $web->date_publisher_price = null;
            $web->linkbuilder = null;
            $web->seo_metrics_date = null;
            $web->copywriting = null;
            $web->created_at = null;
            $web->extra_notes = null;
            $web->setRelation('contact', null);
        }
    }

    private function yesNo($value): string
    {
        if ($value === null) {
            return '';
        }

        return $value ? 'Yes' : 'No';
    }
}
