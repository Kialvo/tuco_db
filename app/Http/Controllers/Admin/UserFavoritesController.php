<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Website;
use Illuminate\Http\Request;
use Yajra\DataTables\DataTables;

class UserFavoritesController extends Controller
{
    public function index(User $user)
    {
        return view('admin.users.favorites', compact('user'));
    }

    public function data(Request $request, User $user)
    {
        $query = Website::withTrashed()
            ->select('websites.*', 'ufd.website_snapshot')
            ->join('user_favorite_domains as ufd', function ($join) use ($user) {
                $join->on('ufd.website_id', '=', 'websites.id')
                    ->where('ufd.user_id', '=', $user->id);
            })
            ->with(['country', 'language', 'contact', 'categories']);

        return DataTables::of($query)
            ->addColumn('country_name', fn ($r) => optional($r->country)->country_name)
            ->addColumn('language_name', fn ($r) => optional($r->language)->name)
            ->addColumn('contact_name', fn ($r) => optional($r->contact)->name)
            ->addColumn('categories_list', fn ($r) => $r->categories->pluck('name')->join(', '))
            ->make(true);
    }

    public function exportCsv(User $user)
    {
        $websites = Website::withTrashed()
            ->select('websites.*', 'ufd.website_snapshot')
            ->join('user_favorite_domains as ufd', function ($join) use ($user) {
                $join->on('ufd.website_id', '=', 'websites.id')
                    ->where('ufd.user_id', '=', $user->id);
            })
            ->with(['country', 'language', 'contact', 'categories'])
            ->get();

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
                $web->betting ? 'Yes' : 'No',
                $web->trading ? 'Yes' : 'No',
                $web->permanent_link ? 'Yes' : 'No',
                $web->more_than_one_link ? 'Yes' : 'No',
                $web->copywriting ? 'Yes' : 'No',
                $web->no_sponsored_tag ? 'Yes' : 'No',
                $web->social_media_sharing ? 'Yes' : 'No',
                $web->post_in_homepage ? 'Yes' : 'No',
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

    public function exportPdf(User $user)
    {
        $websites = Website::withTrashed()
            ->select('websites.*', 'ufd.website_snapshot')
            ->join('user_favorite_domains as ufd', function ($join) use ($user) {
                $join->on('ufd.website_id', '=', 'websites.id')
                    ->where('ufd.user_id', '=', $user->id);
            })
            ->with(['country', 'language', 'contact', 'categories'])
            ->get();

        $html = view('admin.users.favorites_pdf', compact('user', 'websites'))->render();
        $pdf = \PDF::loadHTML($html)->setPaper('a1', 'landscape');

        return $pdf->download('user_'.$user->id.'_favorites_'.date('Y-m-d_His').'.pdf');
    }
}
