<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Website;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
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

    public function exportCsv(Request $request, User $user)
    {
        $columns = $this->adminFavoriteExportColumns();
        $fields = $this->normalizeExportFields(
            $request->input('fields'),
            array_keys($columns)
        );

        $websites = Website::withTrashed()
            ->select('websites.*', 'ufd.website_snapshot')
            ->join('user_favorite_domains as ufd', function ($join) use ($user) {
                $join->on('ufd.website_id', '=', 'websites.id')
                    ->where('ufd.user_id', '=', $user->id);
            })
            ->with(['country', 'language', 'contact', 'categories'])
            ->orderBy('websites.id')
            ->get();

        $filename = 'user_'.$user->id.'_favorites_'.date('Y-m-d_His').'.csv';
        $handle = fopen('php://temp', 'r+');

        // UTF-8 BOM for Excel/Numbers compatibility.
        fwrite($handle, "\xEF\xBB\xBF");

        fputcsv($handle, collect($fields)->map(fn (string $f) => $columns[$f])->all(), ';');

        foreach ($websites as $web) {
            $assoc = $this->adminFavoriteExportRow($web);
            fputcsv($handle, collect($fields)->map(fn (string $f) => $assoc[$f] ?? '')->all(), ';');
        }

        rewind($handle);
        $csvOutput = stream_get_contents($handle);
        fclose($handle);

        return response($csvOutput, 200, [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => "attachment; filename=\"$filename\"",
        ]);
    }

    public function exportPdf(Request $request, User $user)
    {
        @set_time_limit(1200);
        @ini_set('memory_limit', '1024M');

        $columns = $this->adminFavoriteExportColumns();
        $fields = $this->normalizeExportFields(
            $request->input('fields'),
            array_keys($columns)
        );

        $header = collect($fields)->map(fn (string $f) => $columns[$f])->all();
        $title = 'Favorites PDF Export';

        $query = Website::withTrashed()
            ->select('websites.*', 'ufd.website_snapshot')
            ->join('user_favorite_domains as ufd', function ($join) use ($user) {
                $join->on('ufd.website_id', '=', 'websites.id')
                    ->where('ufd.user_id', '=', $user->id);
            })
            ->with([
                'country:id,country_name',
                'language:id,name',
                'contact:id,name',
                'categories:id,name',
            ]);

        $filenameBase = 'user_'.$user->id.'_favorites_'.date('Y-m-d_His');
        $singlePdfMaxRows = 450;
        $rowsPerPart = 400;
        $total = (clone $query)->count();

        if ($total <= $singlePdfMaxRows) {
            $websites = (clone $query)->orderBy('websites.id')->get();
            $rows = [];

            foreach ($websites as $web) {
                $assoc = $this->adminFavoriteExportRow($web);
                $rows[] = collect($fields)->map(fn (string $f) => $assoc[$f] ?? '')->all();
            }

            $pdf = \PDF::setOptions([
                'dpi' => 72,
                'isRemoteEnabled' => false,
            ])->loadView('exports.table_pdf', compact('title', 'header', 'rows'))
                ->setPaper('a1', 'landscape');

            return $pdf->download($filenameBase.'.pdf');
        }

        $tempDir = storage_path('app/tmp_user_favorites_pdf_'.Str::random(10));
        $finalPath = storage_path('app/'.$filenameBase.'_'.Str::random(8).'.pdf');
        if (!is_dir($tempDir)) {
            @mkdir($tempDir, 0775, true);
        }

        $part = 1;
        $partFiles = [];

        try {
            (clone $query)->orderBy('websites.id')->chunkById($rowsPerPart, function ($chunk) use (&$part, &$partFiles, $tempDir, $title, $header, $fields) {
                $rows = [];

                foreach ($chunk as $web) {
                    $assoc = $this->adminFavoriteExportRow($web);
                    $rows[] = collect($fields)->map(fn (string $f) => $assoc[$f] ?? '')->all();
                }

                $pdfBinary = \PDF::setOptions([
                    'dpi' => 72,
                    'isRemoteEnabled' => false,
                ])->loadView('exports.table_pdf', compact('title', 'header', 'rows'))
                    ->setPaper('a1', 'landscape')
                    ->output();

                $partPath = $tempDir.'/part_'.str_pad((string) $part++, 4, '0', STR_PAD_LEFT).'.pdf';
                file_put_contents($partPath, $pdfBinary);
                $partFiles[] = $partPath;

                unset($pdfBinary, $rows);
            }, 'websites.id', 'id');

            $merged = new \setasign\Fpdi\Fpdi();
            foreach ($partFiles as $file) {
                $pageCount = $merged->setSourceFile($file);
                for ($pageNo = 1; $pageNo <= $pageCount; $pageNo++) {
                    $tpl = $merged->importPage($pageNo);
                    $size = $merged->getTemplateSize($tpl);
                    $orientation = ($size['width'] > $size['height']) ? 'L' : 'P';
                    $merged->AddPage($orientation, [$size['width'], $size['height']]);
                    $merged->useTemplate($tpl);
                }
            }
            $merged->Output('F', $finalPath);
        } catch (\Throwable $e) {
            foreach ($partFiles as $file) {
                if (is_file($file)) {
                    @unlink($file);
                }
            }
            if (is_dir($tempDir)) {
                @rmdir($tempDir);
            }

            return response()->json(['error' => 'Could not generate PDF export.'], 500);
        }

        foreach ($partFiles as $file) {
            if (is_file($file)) {
                @unlink($file);
            }
        }
        if (is_dir($tempDir)) {
            @rmdir($tempDir);
        }

        return response()->download($finalPath, $filenameBase.'.pdf')->deleteFileAfterSend(true);
    }

    private function adminFavoriteExportColumns(): array
    {
        return [
            'id' => 'ID',
            'domain_name' => 'Domain',
            'notes' => 'Notes',
            'extra_notes' => 'Internal Notes',
            'status' => 'Status',
            'country_name' => 'Country',
            'language_name' => 'Language',
            'contact_name' => 'Publisher',
            'currency_code' => 'Currency',
            'publisher_price' => 'Publisher Price',
            'no_follow_price' => 'No Follow Price',
            'special_topic_price' => 'Special Topic Price',
            'link_insertion_price' => 'Link Insertion Price',
            'banner_price' => 'Banner EUR',
            'sitewide_link_price' => 'Site-wide EUR',
            'kialvo_evaluation' => 'Price',
            'profit' => 'Profit',
            'date_publisher_price' => 'Date Publisher Price',
            'linkbuilder' => 'Linkbuilder',
            'type_of_website' => 'Type of Website',
            'categories_list' => 'Categories',
            'DA' => 'DA',
            'PA' => 'PA',
            'TF' => 'TF',
            'CF' => 'CF',
            'DR' => 'DR',
            'UR' => 'UR',
            'ZA' => 'ZA',
            'as_metric' => 'AS',
            'seozoom' => 'SEO Zoom',
            'TF_vs_CF' => 'TF vs CF',
            'semrush_traffic' => 'Semrush Traffic',
            'ahrefs_keyword' => 'Ahrefs Keyword',
            'ahrefs_traffic' => 'Ahrefs Traffic',
            'keyword_vs_traffic' => 'Keywords vs Traffic',
            'seo_metrics_date' => 'SEO Metrics Date',
            'betting' => 'Betting',
            'trading' => 'Trading',
            'permanent_link' => 'LINK LIFETIME',
            'more_than_one_link' => 'More than 1 link',
            'copywriting' => 'Copywriting',
            'no_sponsored_tag' => 'Sponsored Tag',
            'social_media_sharing' => 'Social Media Sharing',
            'post_in_homepage' => 'Post in Homepage',
            'created_at' => 'Date Added',
        ];
    }

    private function adminFavoriteExportRow(Website $web): array
    {
        $yesNo = static fn ($value) => $value === null ? '' : ($value ? 'YES' : 'NO');

        return [
            'id' => $web->id,
            'domain_name' => $web->domain_name,
            'notes' => $web->notes,
            'extra_notes' => $web->extra_notes,
            'status' => $web->status,
            'country_name' => optional($web->country)->country_name,
            'language_name' => optional($web->language)->name,
            'contact_name' => $web->contact_id ? optional($web->contact)->name : 'No Publisher',
            'currency_code' => $web->currency_code,
            'publisher_price' => $web->publisher_price,
            'no_follow_price' => $web->no_follow_price,
            'special_topic_price' => $web->special_topic_price,
            'link_insertion_price' => $web->link_insertion_price,
            'banner_price' => $web->banner_price,
            'sitewide_link_price' => $web->sitewide_link_price,
            'kialvo_evaluation' => $web->kialvo_evaluation,
            'profit' => $web->profit,
            'date_publisher_price' => $web->date_publisher_price,
            'linkbuilder' => $web->linkbuilder,
            'type_of_website' => $web->type_of_website,
            'categories_list' => $web->categories->pluck('name')->join(', '),
            'DA' => $web->DA,
            'PA' => $web->PA,
            'TF' => $web->TF,
            'CF' => $web->CF,
            'DR' => $web->DR,
            'UR' => $web->UR,
            'ZA' => $web->ZA,
            'as_metric' => $web->as_metric,
            'seozoom' => $web->seozoom,
            'TF_vs_CF' => $web->TF_vs_CF,
            'semrush_traffic' => $web->semrush_traffic,
            'ahrefs_keyword' => $web->ahrefs_keyword,
            'ahrefs_traffic' => $web->ahrefs_traffic,
            'keyword_vs_traffic' => $web->keyword_vs_traffic,
            'seo_metrics_date' => $web->seo_metrics_date,
            'betting' => $yesNo($web->betting),
            'trading' => $yesNo($web->trading),
            'permanent_link' => $yesNo($web->permanent_link),
            'more_than_one_link' => $yesNo($web->more_than_one_link),
            'copywriting' => $web->copywriting === null ? '' : ($web->copywriting ? 'PROVIDED' : 'NOT PROVIDED'),
            'no_sponsored_tag' => $web->no_sponsored_tag === null ? '' : ($web->no_sponsored_tag ? 'NO' : 'YES'),
            'social_media_sharing' => $yesNo($web->social_media_sharing),
            'post_in_homepage' => $yesNo($web->post_in_homepage),
            'created_at' => $web->created_at?->format('Y-m-d H:i:s'),
        ];
    }

    private function normalizeExportFields($requestedFields, array $allFields): array
    {
        if (is_string($requestedFields)) {
            $requestedFields = array_filter(array_map('trim', explode(',', $requestedFields)));
        }

        if (!is_array($requestedFields) || empty($requestedFields)) {
            return $allFields;
        }

        $fields = array_values(array_intersect($requestedFields, $allFields));
        return empty($fields) ? $allFields : $fields;
    }
}
