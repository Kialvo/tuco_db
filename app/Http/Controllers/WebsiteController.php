<?php

namespace App\Http\Controllers;

use AmrShawky\Currency\Facade\Currency;
use App\Models\RollbackWebsite;
use App\Models\Website;
use App\Models\Country;
use App\Models\Language;
use App\Models\Contact;
use App\Models\Category;
use App\Support\MenfordPriceCalculator;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Yajra\DataTables\DataTables;

class WebsiteController extends Controller
{
    public const BULK_EDITABLE = [
        'status','language_id','country_id','linkbuilder','type_of_website',
        // SEO METRICS
        'DR','UR','DA','PA','TF','CF','ZA','as_metric',
        'seozoom','semrush_traffic','ahrefs_keyword','ahrefs_traffic','keyword_vs_traffic',
        'publisher_price','no_follow_price','special_topic_price',
        'link_insertion_price','banner_price','sitewide_link_price',
        'kialvo_evaluation','profit',
        'date_publisher_price',
        'seo_metrics_date',
        'date_kialvo_evaluation',   // ← add this
            // BOOLEAN FLAGS
            'betting','trading','permanent_link','more_than_one_link',
            'copywriting','no_sponsored_tag','social_media_sharing','post_in_homepage',
        'category_ids',              // <── NEW
        self::FIELD_RECALC,          // <── NEW
    ];
    public const FIELD_RECALC = 'recalculate_totals';

    /* ------------------------------------------------------------------ */
    /*  NEW: columns that drive the auto-recalculation                    */
    /* ------------------------------------------------------------------ */
    private const DRIVER_COLS = [
        'publisher_price','banner_price','sitewide_link_price',
        'kialvo_evaluation','ahrefs_keyword','ahrefs_traffic','language_id',
    ];

    private function isGuestUser(): bool
    {
        return auth()->check() && auth()->user()->isGuest();
    }
    /**
     * Display the index page with filters and DataTable.
     */
    public function index()
    {
        // Load foreign data for the filter form.
        $countries  = Country::all();
        $languages  = Language::all();
        $contacts   = Contact::all();
        $categories = Category::all();

        return view('websites.index', compact('countries','languages','contacts','categories'));
    }


    /**
     * Return JSON data for DataTables.
     */
    public function getData(Request $request)
    {
        $isGuestUser = $this->isGuestUser();
        $favoriteIds = [];
        if ($isGuestUser) {
            $favoriteIds = DB::table('user_favorite_domains')
                ->where('user_id', auth()->id())
                ->pluck('website_id')
                ->all();
            $favoriteIds = array_fill_keys($favoriteIds, true);
        }

        // 1) base query + eager-loads
        $query = Website::with(['country', 'language', 'contact', 'categories']);

        // 2) one single call applies *all* filters
        $this->applyFilters($request, $query);

        // 2b) guest-only favorites filter
        if ($isGuestUser && $request->boolean('favorites_only')) {
            $favKeys = array_keys($favoriteIds);
            if (empty($favKeys)) {
                $query->whereRaw('1 = 0');
            } else {
                $query->whereIn('websites.id', $favKeys);
            }
        }

        // 3) feed into Yajra
        $dataTable = DataTables::of($query)
            ->addColumn('is_favorite', fn ($r) => $isGuestUser && isset($favoriteIds[$r->id]))
            ->addColumn('banner_price',        fn($r)=>$r->banner_price)
            ->addColumn('sitewide_link_price', fn($r)=>$r->sitewide_link_price)
            ->addColumn('country_name',    fn ($r) => optional($r->country)->country_name)
            ->addColumn('language_name',   fn ($r) => optional($r->language)->name)
            ->addColumn('contact_name',    fn ($r) => $isGuestUser ? null : optional($r->contact)->name)
            ->addColumn('categories_list', fn ($r) => $r->categories->pluck('name')->join(', '))
            ->addColumn('action', function($row) use ($isGuestUser) {
                $viewUrl = route('websites.show', $row->id);

                if ($isGuestUser) {
                    return '
            <a href="'.$viewUrl.'"
               class="inline-flex items-center bg-green-600 text-white px-3 py-1 rounded shadow-sm
                      hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500">
                <i class="fas fa-eye mr-1"></i> View
            </a>
        ';
                }

                // Check if this website is soft-deleted (trashed)
                if ($row->trashed()) {
                    $restoreUrl = route('websites.restore', $row->id);
                    return '
            <form action="'.$restoreUrl.'" method="POST" style="display:inline;">
                '.csrf_field().'
                <button
                    onclick="return confirm(\'Are you sure you want to restore this website?\')"
                    class="inline-flex items-center bg-green-600 text-white px-3 py-1 rounded shadow-sm
                           hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500">
                    <i class="fas fa-undo-alt mr-1"></i> Restore
                </button>
            </form>
        ';
                }

                // Otherwise (not trashed), show View, Edit, Delete
                $editUrl   = route('websites.edit', $row->id);
                $deleteUrl = route('websites.destroy', $row->id);

                return '
        <div class="inline-flex space-x-1">
            <!-- VIEW -->
            <a href="'.$viewUrl.'"
               class="inline-flex items-center bg-green-600 text-white px-3 py-1 rounded shadow-sm
                      hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500">
                <i class="fas fa-eye mr-1"></i> View
            </a>

            <!-- EDIT -->
            <a href="'.$editUrl.'"
               class="inline-flex items-center bg-cyan-600 text-white px-3 py-1 rounded shadow-sm
                      hover:bg-cyan-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-cyan-500">
                <i class="fas fa-pen mr-1"></i> Edit
            </a>

            <!-- DELETE -->
            <form action="'.$deleteUrl.'" method="POST" style="display:inline-block;">
                '.csrf_field().method_field("DELETE").'
                <button
                    onclick="return confirm(\'Are you sure you want to delete this website?\')"
                    class="inline-flex items-center bg-red-600 text-white px-3 py-1 rounded shadow-sm
                           hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500">
                    <i class="fas fa-trash mr-1"></i> Delete
                </button>
            </form>
        </div>

    ';
            });

        if ($isGuestUser) {
            $dataTable
                ->editColumn('status', fn() => null)
                ->editColumn('currency_code', fn() => null)
                ->editColumn('publisher_price', fn() => null)
                ->editColumn('no_follow_price', fn() => null)
                ->editColumn('special_topic_price', fn() => null)
                ->editColumn('link_insertion_price', fn() => null)
                ->editColumn('banner_price', fn() => null)
                ->editColumn('sitewide_link_price', fn() => null)
                ->editColumn('kialvo_evaluation', fn() => null)
                ->editColumn('profit', fn() => null)
                ->editColumn('date_publisher_price', fn() => null)
                ->editColumn('linkbuilder', fn() => null)
                ->editColumn('seo_metrics_date', fn() => null)
                ->editColumn('copywriting', fn() => null)
                ->editColumn('created_at', fn() => null)
                ->editColumn('extra_notes', fn() => null)
                ->removeColumn('contact_id');
        }

        return $dataTable->rawColumns(['action'])->make(true);
    }


    // ===============================
//  CSV EXPORT (All Fields)
// ===============================
    public function exportCsv(Request $request)
    {
        if ($this->isGuestUser()) {
            return $this->exportGuestCsv($request);
        }

        $columns = $this->adminWebsiteExportColumns();
        $fields = $this->normalizeExportFields(
            $request->input('fields'),
            array_keys($columns)
        );

        $query = Website::with(['country', 'language', 'contact', 'categories']);
        $this->applyFilters($request, $query);
        $websites = $query->get();

        $csvData = [];
        $csvData[] = collect($fields)->map(fn (string $f) => $columns[$f])->all();

        foreach ($websites as $web) {
            $rowAssoc = $this->adminWebsiteExportRow($web);
            $csvData[] = collect($fields)->map(fn (string $f) => $rowAssoc[$f] ?? '')->all();
        }

        $filename = 'websites_export_'.date('Y-m-d_His').'.csv';
        $handle   = fopen('php://temp', 'r+');
        foreach ($csvData as $row) {
            fputcsv($handle, $row);
        }
        rewind($handle);
        $csvOutput = stream_get_contents($handle);
        fclose($handle);

        return response($csvOutput, 200, [
            'Content-Type'        => 'text/csv',
            'Content-Disposition' => "attachment; filename=\"$filename\"",
        ]);
    }

    private function exportGuestCsv(Request $request)
    {
        $favoriteIds = $this->guestFavoriteIds();

        $query = Website::with(['country','language','contact','categories']);
        $this->applyFilters($request, $query);
        $this->applyGuestFavoritesFilter($request, $query, $favoriteIds);

        $websites = $query->get();

        $csvData = [];
        $csvData[] = [
            'Domain',
            'Notes',
            'Country',
            'Language',
            'Sensitive Topic Price',
            'Price',
            'Type of Website',
            'Categories',
            'DA',
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
            'Betting',
            'Trading',
            'LINK LIFETIME',
            'More than 1 link',
            'Sponsored Tag',
            'Social Media Sharing',
            'Post in Homepage',
        ];

        $yn = fn($value) => $value === null ? '' : ($value ? 'YES' : 'NO');
        $sponsored = fn($value) => $value === null ? '' : ($value ? 'NO' : 'YES');

        foreach ($websites as $web) {
            $csvData[] = [
                $web->domain_name,
                $web->notes,
                optional($web->country)->country_name,
                optional($web->language)->name,
                $web->sensitive_topic_price,
                $web->price,
                $web->type_of_website,
                $web->categories->pluck('name')->join(', '),
                $web->DA,
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
                $yn($web->betting),
                $yn($web->trading),
                $yn($web->permanent_link),
                $yn($web->more_than_one_link),
                $sponsored($web->no_sponsored_tag),
                $yn($web->social_media_sharing),
                $yn($web->post_in_homepage),
            ];
        }

        $filename = 'websites_export_'.date('Y-m-d_His').'.csv';
        $handle   = fopen('php://temp', 'r+');
        foreach ($csvData as $row) {
            fputcsv($handle, $row);
        }
        rewind($handle);
        $csvOutput = stream_get_contents($handle);
        fclose($handle);

        return response($csvOutput, 200, [
            'Content-Type'        => 'text/csv',
            'Content-Disposition' => "attachment; filename=\"$filename\"",
        ]);
    }

    private function exportGuestPdf(Request $request)
    {
        // Large unfiltered exports are heavy for DOMPDF; raise limits for this request only.
        @set_time_limit(1200);
        @ini_set('memory_limit', '1024M');

        $favoriteIds = $this->guestFavoriteIds();

        $query = Website::select([
            'id',
            'domain_name',
            'notes',
            'country_id',
            'language_id',
            'sensitive_topic_price',
            'price',
            'type_of_website',
            'DA',
            'PA',
            'TF',
            'CF',
            'DR',
            'UR',
            'ZA',
            'as_metric',
            'seozoom',
            'TF_vs_CF',
            'semrush_traffic',
            'ahrefs_keyword',
            'ahrefs_traffic',
            'keyword_vs_traffic',
            'betting',
            'trading',
            'permanent_link',
            'more_than_one_link',
            'no_sponsored_tag',
            'social_media_sharing',
            'post_in_homepage',
        ])->with([
            'country:id,country_name',
            'language:id,name',
            'categories:id,name',
        ]);
        $this->applyFilters($request, $query);
        $this->applyGuestFavoritesFilter($request, $query, $favoriteIds);

        $user = $request->user();
        $filenameBase = 'websites_export_'.date('Y-m-d_His');
        $singlePdfMaxRows = 450;
        $rowsPerPart = 400;
        $total = (clone $query)->count();

        if ($total <= $singlePdfMaxRows) {
            $websites = (clone $query)->orderBy('id')->get();
            $pdf = \PDF::setOptions([
                'dpi' => 72,
                'isRemoteEnabled' => false,
            ])->loadView('websites.favorites_pdf', compact('user', 'websites', 'favoriteIds'))
                ->setPaper('a1', 'landscape');

            return $pdf->download($filenameBase.'.pdf');
        }

        $tempDir = storage_path('app/tmp_guest_pdf_'.Str::random(10));
        $finalPath = storage_path('app/'.$filenameBase.'_'.Str::random(8).'.pdf');
        if (!is_dir($tempDir)) {
            @mkdir($tempDir, 0775, true);
        }

        $part = 1;
        $partFiles = [];

        try {
            (clone $query)->orderBy('id')->chunkById($rowsPerPart, function ($chunk) use (&$part, &$partFiles, $tempDir, $user, $favoriteIds) {
                $websites = $chunk->values();
                $pdfBinary = \PDF::setOptions([
                    'dpi' => 72,
                    'isRemoteEnabled' => false,
                ])->loadView('websites.favorites_pdf', compact('user', 'websites', 'favoriteIds'))
                    ->setPaper('a1', 'landscape')
                    ->output();

                $partPath = $tempDir.'/part_'.str_pad((string)$part++, 4, '0', STR_PAD_LEFT).'.pdf';
                file_put_contents($partPath, $pdfBinary);
                $partFiles[] = $partPath;

                unset($pdfBinary, $websites);
            }, 'id');

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

    private function guestFavoriteIds(): array
    {
        $ids = DB::table('user_favorite_domains')
            ->where('user_id', auth()->id())
            ->pluck('website_id')
            ->all();

        return array_fill_keys($ids, true);
    }

    private function applyGuestFavoritesFilter(Request $request, $query, array $favoriteIds): void
    {
        if (! $request->boolean('favorites_only')) {
            return;
        }

        $ids = array_keys($favoriteIds);
        if (empty($ids)) {
            $query->whereRaw('1 = 0');
            return;
        }

        $query->whereIn('websites.id', $ids);
    }

    private function adminWebsiteExportColumns(): array
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
            'price' => 'Price',
            'sensitive_topic_price' => 'Sensitive Topic Price',
            'link_insertion_price' => 'Link Insertion Price',
            'banner_price' => 'Banner €',
            'sitewide_link_price' => 'Site-wide €',
            'kialvo_evaluation' => 'Kialvo Evaluation',
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

    private function adminWebsiteExportRow(Website $web): array
    {
        $yesNo = static fn($value) => $value === null ? '' : ($value ? 'YES' : 'NO');

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
            'price' => $web->price,
            'sensitive_topic_price' => $web->sensitive_topic_price,
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


    private function mirrorOriginals(array &$v): void
    {
        $map = [
            'publisher_price'        => 'original_publisher_price',
            'link_insertion_price'   => 'original_link_insertion_price',
            'no_follow_price'        => 'original_no_follow_price',
            'special_topic_price'    => 'original_special_topic_price',
            'banner_price'        => 'original_banner_price',
            'sitewide_link_price' => 'original_sitewide_link_price',
        ];
        foreach ($map as $dst => $src) {
            if (empty($v[$dst]) && !empty($v[$src])) {
                $v[$dst] = $v[$src];
            }
        }
    }

    /**
     * Apply every possible filter coming from the front-end.
     * Called by DataTable, CSV export and PDF export – the SINGLE
     * source of truth for filtering logic.
     */
    protected function applyFilters(Request $request, $query)
    {
        $isGuestUser = $this->isGuestUser();
        /* ───── simple string / exact-match filters ───── */
        if ($v = $request->domain_name)     $query->where('domain_name', 'like', "%$v%");
        if ($v = $request->type_of_website) $query->where('type_of_website', $v);
        if ($isGuestUser) {
            $query->where(function ($q) {
                $q->whereNull('status')
                  ->orWhereRaw('LOWER(status) <> ?', ['past']);
            });
        } elseif ($v = $request->status) {
            $query->where('status', $v);
        }

        /* ───── FK equality filters ───── */
        if ($v = $request->country_id)  $query->where('country_id',  $v);
        if ($v = $request->language_id) $query->where('language_id', $v);


        /* ───── FK include / exclude lists ───── */
        if (!empty($request->country_ids_include)) {
            $ids = is_array($request->country_ids_include)
                ? $request->country_ids_include              // existing behaviour
                : explode(',', $request->country_ids_include); // <─ NEW: split string
            $query->whereIn('country_id', $ids);
        }

        if (!empty($request->country_ids_exclude)) {
            $ids = is_array($request->country_ids_exclude)
                ? $request->country_ids_exclude              // existing behaviour
                : explode(',', $request->country_ids_exclude); // <─ NEW: split string
            $query->whereNotIn('country_id', $ids);
        }


        /* ───── helper for numeric ranges ───── */
        $rng = function ($min, $max, $col) use ($query) {
            if ($min !== null && $max !== null) $query->whereBetween($col, [$min, $max]);
            elseif ($min !== null)              $query->where($col, '>=', $min);
            elseif ($max !== null)              $query->where($col, '<=', $max);
        };

        if (! $isGuestUser) {
            $rng($request->publisher_price_min,    $request->publisher_price_max,    'publisher_price');
            $rng($request->profit_min,             $request->profit_max,             'profit');
            $rng($request->banner_price_min,       $request->banner_price_max,       'banner_price');
            $rng($request->sitewide_price_min,     $request->sitewide_price_max,     'sitewide_link_price');
        }

        $rng($request->price_min,                  $request->price_max,              'price');
        $rng($request->sensitive_topic_price_min,  $request->sensitive_topic_price_max, 'sensitive_topic_price');

        if (! $isGuestUser) {
            $rng($request->kialvo_min,         $request->kialvo_max,             'kialvo_evaluation');
        }
        $rng($request->DA_min,                 $request->DA_max,                 'DA');
        $rng($request->PA_min,                 $request->PA_max,                 'PA');
        $rng($request->TF_min,                 $request->TF_max,                 'TF');
        $rng($request->CF_min,                 $request->CF_max,                 'CF');
        $rng($request->TF_VS_CF_min,           $request->TF_VS_CF_max,           'TF_vs_CF');
        $rng($request->DR_min,                 $request->DR_max,                 'DR');
        $rng($request->UR_min,                 $request->UR_max,                 'UR');
        $rng($request->ZA_min,                 $request->ZA_max,                 'ZA');
        $rng($request->SR_min,                 $request->SR_max,                 'as_metric');          // SR
        $rng($request->semrush_traffic_min,    $request->semrush_traffic_max,    'semrush_traffic');
        $rng($request->ahrefs_keyword_min,     $request->ahrefs_keyword_max,     'ahrefs_keyword');
        $rng($request->ahrefs_traffic_min,     $request->ahrefs_traffic_max,     'ahrefs_traffic');
        $rng($request->keyword_vs_traffic_min, $request->keyword_vs_traffic_max, 'keyword_vs_traffic');

        /* ───── booleans coming from check-boxes ───── */
        foreach ([
                     'more_than_one_link',
                     'permanent_link',
                     'copywriting',
                     'no_sponsored_tag',
                     'social_media_sharing',
                     'post_in_homepage',
                     'betting',
                     'trading',
                 ] as $flag) {
            if ($request->boolean($flag)) $query->where($flag, true);
        }

        /* ───── special “no contact” flag ───── */
        /* ───── Publisher / contact filters ───── */
        if (! $isGuestUser) {
            if ($request->boolean('no_contact')) {
                // Only websites with no publisher
                $query->whereNull('contact_id');
            } elseif ($v = $request->contact_id) {
                // Websites belonging to a specific publisher
                $query->where('contact_id', $v);
            }
        }


        /* ───── many-to-many categories ───── */
//        if ($ids = $request->category_ids) {
//            $query->whereHas('categories', fn ($q) => $q->whereIn('categories.id', $ids));
//        }

        if (!empty($request->category_ids)) {
            $ids = is_array($request->category_ids)
                ? $request->category_ids              // existing behaviour
                : explode(',', $request->category_ids); // <─ NEW: split string
            $query->whereHas('categories', fn ($q) => $q->whereIn('categories.id', $ids));
        }
        /* ───── soft-delete toggle ───── */
        if (! $isGuestUser && $request->boolean('show_deleted')) {
            $query->onlyTrashed();
        }

        return $query;          // allows fluent chaining in exports
    }

    public function exportPdf(Request $request)
    {
        if ($this->isGuestUser()) {
            return $this->exportGuestPdf($request);
        }

        @set_time_limit(1200);
        @ini_set('memory_limit', '1024M');

        try {
            if (!view()->exists('exports.table_pdf')) {
                throw new \Exception('PDF template not found');
            }

            $columns = $this->adminWebsiteExportColumns();
            $fields = $this->normalizeExportFields(
                $request->input('fields'),
                array_keys($columns)
            );
            $header = collect($fields)->map(fn (string $f) => $columns[$f])->all();
            $title = 'Websites PDF Export';

            $query = Website::with([
                'country:id,country_name',
                'language:id,name',
                'contact:id,name',
                'categories:id,name',
            ]);
            $this->applyFilters($request, $query);

            $filenameBase = 'websites_export_'.date('Y-m-d_His');
            $singlePdfMaxRows = 450;
            $rowsPerPart = 400;
            $total = (clone $query)->count();

            if ($total <= $singlePdfMaxRows) {
                $websites = (clone $query)->orderBy('id')->get();
                $rows = [];
                foreach ($websites as $web) {
                    $assoc = $this->adminWebsiteExportRow($web);
                    $rows[] = collect($fields)->map(fn (string $f) => $assoc[$f] ?? '')->all();
                }

                $pdf = \PDF::setOptions([
                    'dpi' => 72,
                    'isRemoteEnabled' => false,
                ])->loadView('exports.table_pdf', compact('title', 'header', 'rows'))
                    ->setPaper('a1', 'landscape');

                return $pdf->download($filenameBase.'.pdf');
            }

            $tempDir = storage_path('app/tmp_websites_pdf_'.Str::random(10));
            $finalPath = storage_path('app/'.$filenameBase.'_'.Str::random(8).'.pdf');
            if (!is_dir($tempDir)) {
                @mkdir($tempDir, 0775, true);
            }

            $part = 1;
            $partFiles = [];

            try {
                (clone $query)->orderBy('id')->chunkById($rowsPerPart, function ($chunk) use (&$part, &$partFiles, $tempDir, $title, $header, $fields) {
                    $rows = [];
                    foreach ($chunk as $web) {
                        $assoc = $this->adminWebsiteExportRow($web);
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
                }, 'id');

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
                throw $e;
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
        } catch (\Throwable $e) {
            \Log::error('PDF Generation Error: '.$e->getMessage());
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    /**
     * Show the create form.
     */
    public function create()
    {
        $countries  = Country::all();
        $languages  = Language::all();
        $contacts   = Contact::all();
        $categories = Category::all();

        return view('websites.create', compact('countries', 'languages', 'contacts', 'categories'));
    }

    /**
     * Store a new website.
     */
    public function store(Request $request)
    {


        // 1) Validate all fields EXCEPT we do not rely on user input for 'automatic_evaluation'
        // 1) Validate your form inputs
        $validated = $this->validateForm($request);

        foreach (['date_publisher_price',
                     'date_kialvo_evaluation',
                     'seo_metrics_date'] as $f) {
            $validated[$f] = $this->euDate($validated[$f] ?? null);
        }

        // 2) Compute the automatic evaluation from your formula
        //    Formula: {DA}*2.4 + {TF}*1.45 + {DR}*0.5 + IF({SR}>=9700, {SR}/15000, 0)*1.35
        $da = $validated['DA'] ?? 0;
        $tf = $validated['TF'] ?? 0;
        $dr = $validated['DR'] ?? 0;
        // If your "SR" is stored in "as_metric", do:
        $sr = $validated['semrush_traffic'] ?? 0;

        //dd($da,$tf,$dr);
        $autoEvaluation = ($da * 2.4) + ($tf * 1.45) + ($dr * 0.5);


        if ($sr >= 9700) {
            $autoEvaluation += ($sr / 15000) * 1.35;
        }

        // 3) Override / set 'automatic_evaluation' in the $validated array
        $validated['automatic_evaluation'] = $autoEvaluation;

        // 3) Compute 'profit' => kialvo_evaluation - publisher_price
        $kialvoVal       = $validated['kialvo_evaluation'] ?? 0;
        $publisherPrice  = $validated['publisher_price'] ?? 0;
        $profit          = $kialvoVal - $publisherPrice;
        $validated['profit'] = $profit;
        $validated['price'] = MenfordPriceCalculator::calculate(
            $this->publisherPriceForPriceFormula($validated),
            isset($validated['language_id']) ? (int) $validated['language_id'] : null
        );

        // 3) Compute 'TF_vs_CF' =>
        $TF   = $validated['TF'] ?? 0;
        $CF  = $validated['CF'] ?? 0;

        if($CF == 0 || $CF == null){
            $TF_vs_CF = 0;
        }else{
            $TF_vs_CF         = ($TF/$CF);
        }

        $validated['TF_vs_CF'] = $TF_vs_CF;
        $ahrefsKeyword = $validated['ahrefs_keyword'] ?? 0;
        $ahrefsTraffic = $validated['ahrefs_traffic'] ?? 0;

        if ($ahrefsTraffic > 0) {
            $validated['keyword_vs_traffic'] = round($ahrefsKeyword / $ahrefsTraffic, 2);
        } else {
            $validated['keyword_vs_traffic'] = 0;
        }


        // 4) Create the new Website using the final data
        $website = Website::create($validated);

        // If you have categories
        if ($request->has('category_ids')) {
            $website->categories()->sync($request->category_ids);
        }

        return redirect()->route('websites.edit', $website)
            ->with('status', 'Website created successfully and ready to edit!');
    }


    /**
     * Display a single website.
     */
    public function show(Website $website)
    {
        if ($this->isGuestUser()) {
            $status = strtolower(trim((string) $website->status));
            if ($status === 'past') {
                if (request()->expectsJson() || request()->ajax()) {
                    return response()->json(['message' => 'Not found'], 404);
                }
                return redirect()->route('websites.index');
            }
        }
        $website->load(['country', 'language', 'contact', 'categories']);
        return view('websites.show', compact('website'));
    }

    /**
     * Show the edit form.
     */
    public function edit(Website $website)
    {
        $website->load(['country', 'language', 'contact', 'categories']);

        $countries  = Country::all();
        $languages  = Language::all();
        $contacts   = Contact::all();
        $categories = Category::all();

        return view('websites.edit', compact('website', 'countries', 'languages', 'contacts', 'categories'));
    }

    /**
     * Update an existing website.
     */
    public function update(Request $request, Website $website)
    {
        // 1) Validate your form inputs
        $validated = $this->validateForm($request);

        foreach ([
                     'date_publisher_price',
                     'date_kialvo_evaluation',
                     'seo_metrics_date',
                 ] as $field) {
            $validated[$field] = $this->euDate($validated[$field] ?? null);
        }

        // 2) Compute automatic evaluation
        $da = $validated['DA'] ?? 0;
        $tf = $validated['TF'] ?? 0;
        $dr = $validated['DR'] ?? 0;
        $sr = $validated['semrush_traffic'] ?? 0;

        $autoEvaluation = ($da * 2.4) + ($tf * 1.45) + ($dr * 0.5);

        if ($sr >= 9700) {
            $autoEvaluation += ($sr / 15000) * 1.35;
        }

        $validated['automatic_evaluation'] = $autoEvaluation;

        // 3) Compute 'profit' => kialvo_evaluation - publisher_price
        $kialvoVal       = $validated['kialvo_evaluation'] ?? 0;
        $publisherPrice  = $validated['publisher_price'] ?? 0;
        $profit          = $kialvoVal - $publisherPrice;
        $validated['profit'] = $profit;
        $validated['price'] = MenfordPriceCalculator::calculate(
            $this->publisherPriceForPriceFormula($validated),
            isset($validated['language_id']) ? (int) $validated['language_id'] : null
        );

        // 3) Compute 'TF_vs_CF' =>
        $TF   = $validated['TF'] ?? 0;
        $CF  = $validated['CF'] ?? 0;

        if($CF == 0 || $CF == null){
            $TF_vs_CF = 0;
        }else{
            $TF_vs_CF         = ($TF/$CF);
        }

        $validated['TF_vs_CF'] = $TF_vs_CF;

        $ahrefsKeyword = $validated['ahrefs_keyword'] ?? 0;
        $ahrefsTraffic = $validated['ahrefs_traffic'] ?? 0;

        if ($ahrefsTraffic > 0) {
            $validated['keyword_vs_traffic'] = round($ahrefsKeyword / $ahrefsTraffic, 2);
        } else {
            $validated['keyword_vs_traffic'] = 0;
        }

        $website->update($validated);

        // Sync categories
        if ($request->has('category_ids')) {
            $website->categories()->sync($request->category_ids);
        } else {
            $website->categories()->sync([]);
        }

        return redirect()->route('websites.index')
            ->with('status', 'Website updated successfully!');
    }

    public function bulkUpdate(Request $request)
    {
        $data = $request->validate([
            'ids'   => 'required',
            'field' => ['required','string', function($a,$v,$f){
                if (!in_array($v, self::BULK_EDITABLE, true)) $f('Field not allowed for bulk edit.');
            }],
            'value' => 'sometimes',
        ]);

        // normalize ids: array or comma-separated string → array<int>
        $ids = is_array($request->ids)
            ? array_values($request->ids)
            : array_filter(array_map('intval', explode(',', (string)$request->ids)));

        if (empty($ids)) {
            return response()->json(['message' => 'No valid ids provided'], 422);
        }

        $field = $data['field'];
        $value = $request->input('value', null);
        if ($value === '') $value = null;

        $token = (string) Str::uuid();                  // for undo

        DB::transaction(function () use ($ids, $field, &$value, $token) {
            $rows = Website::with('categories')->whereIn('id', $ids)->get();

            foreach ($rows as $row) {
                RollbackWebsite::create([
                    'token'      => $token,
                    'website_id' => $row->id,
                    'snapshot'   => [
                        'attributes' => $row->getAttributes(),
                        'categories' => $row->categories->pluck('id')->all(),
                    ],
                ]);
            }

            /* --------- 2) pseudo-field “Re-calculate totals” ------------- */
            if ($field === self::FIELD_RECALC) {
                foreach ($rows as $w) {
                    $payload = $w->getAttributes();
                    $this->applyAutoCalculations($payload);

                    $w->fill([
                        'price'               => $payload['price'],
                        'profit'              => $payload['profit'],
                        'total_cost'          => $payload['total_cost'],
                        'total_revenues'      => $payload['total_revenues'],
                        'keyword_vs_traffic'  => $payload['keyword_vs_traffic'],
                        'TF_vs_CF'            => $payload['TF_vs_CF'],
                    ])->save();
                }
                return;                                     // done
            }

            /* --------- 3) many-to-many  categories ----------------------- */
            if ($field === 'category_ids') {
                // Accept both array and comma-separated string
                $catIds = is_array($value)
                    ? array_filter($value)
                    : array_filter(explode(',', (string) $value));

                foreach ($rows as $w) {
                    $w->categories()->sync($catIds);        // replace whole set
                }
                return;
            }

            /* --------- 4) date strings dd/mm/yyyy → yyyy-mm-dd ----------- */
            if ((Str::endsWith($field, '_date') || Str::startsWith($field, 'date_')) && $value !== null) {
                $value = preg_match('#^\d{2}/\d{2}/\d{4}$#', $value)
                    ? Carbon::createFromFormat('d/m/Y', $value)->toDateString()
                    : Carbon::parse($value)->toDateString();
            }


            /* --------- 5) regular scalar bulk updates -------------------- */
            foreach ($rows as $w) {
                // Special handling for USD price fields: mirror into original_* and convert
                $priceFields = [
                    'publisher_price','link_insertion_price','no_follow_price','special_topic_price',
                    'banner_price','sitewide_link_price',
                ];

                if (in_array($field, $priceFields, true)) {
                    // If site is USD, store the raw value in original_* and convert to EUR for display field
                    if (strtoupper((string) $w->currency_code) === 'USD' && $value !== null && $value !== '') {
                        $originalMap = [
                            'publisher_price'      => 'original_publisher_price',
                            'link_insertion_price' => 'original_link_insertion_price',
                            'no_follow_price'      => 'original_no_follow_price',
                            'special_topic_price'  => 'original_special_topic_price',
                            'banner_price'         => 'original_banner_price',
                            'sitewide_link_price'  => 'original_sitewide_link_price',
                        ];
                        $origField = $originalMap[$field] ?? null;
                        if ($origField) {
                            $w->{$origField} = $value;
                        }

                        try {
                            $converted = \AmrShawky\Currency\Facade\Currency::convert()
                                ->from('USD')->to('EUR')->amount((float) $value)->get();
                            $w->{$field} = $converted;
                        } catch (\Throwable $e) {
                            // Fallback: store numeric as-is
                            $w->{$field} = $value;
                        }
                    } else {
                        $w->{$field} = $value;
                    }
                } else {
                    $w->{$field} = $value;
                }

                /* re-run auto KPIs when a driver field changes */
                if (in_array($field, self::DRIVER_COLS, true)) {
                    $payload = $w->getAttributes();
                    $this->applyAutoCalculations($payload);

                    $w->fill([
                        'price'              => $payload['price'],
                        'profit'             => $payload['profit'],
                        'total_cost'         => $payload['total_cost'],
                        'total_revenues'     => $payload['total_revenues'],
                        'keyword_vs_traffic' => $payload['keyword_vs_traffic'],
                        'TF_vs_CF'           => $payload['TF_vs_CF'],
                    ]);
                }

                $w->save();
            }
        });

        return response()->json([
            'message'    => 'Updated '.count($ids).' record(s).',
            'undo_token' => $token,
        ]);
    }


    /**
     * Simple derived-metric helper (extend as needed).
     */
    private function applyAutoCalculations(array &$d): void
    {
        $cost  = (float) ($d['publisher_price'] ?? 0);
        $d['price'] = MenfordPriceCalculator::calculate(
            $this->publisherPriceForPriceFormula($d),
            isset($d['language_id']) ? (int) $d['language_id'] : null
        );

        $rev   = (float) ($d['kialvo_evaluation'] ?? 0)
            + (float) ($d['banner_price'] ?? 0)
            + (float) ($d['sitewide_link_price'] ?? 0);

        $d['profit']         = $rev - $cost;
        $d['total_cost']     = $cost;
        $d['total_revenues'] = $rev;

        /* keyword_vs_traffic */
        $kw  = (float) ($d['ahrefs_keyword']  ?? 0);
        $tr  = (float) ($d['ahrefs_traffic']  ?? 0);
        $d['keyword_vs_traffic'] = $tr>0 ? round($kw/$tr,2) : 0;

        /* TF vs CF */
        $tf = (float) ($d['TF'] ?? 0);
        $cf = (float) ($d['CF'] ?? 0);
        $d['TF_vs_CF'] = $cf ? round($tf/$cf,2) : 0;
    }



    /**
     * Delete a website.
     */
    /**
     * Soft Delete (instead of permanent delete).
     */
    public function destroy(Website $website)
    {
        $website->delete(); // sets deleted_at
        return redirect()->route('websites.index')->with('status', 'Website soft-deleted!');
    }

    public function restore($id)
    {
        // Retrieve the trashed record
        $website = Website::onlyTrashed()->findOrFail($id);

        // Restore it
        $website->restore();

        return redirect()->route('websites.index')->with('status', 'Website restored successfully!');
    }


    /** Convert `dd/mm/yyyy` → `yyyy-mm-dd` (or return null/unchanged) */
    private function euDate(?string $v): ?string
    {
        if (!$v) return null;
        try {
            return \Carbon\Carbon::createFromFormat('d/m/Y', $v)->format('Y-m-d');
        } catch (\Exception $e) {
            return $v;                 // let validation scream if format is wrong
        }
    }
    private function convertUsdFieldsToEur(array &$validated, array $fields)
    {
        // Only convert if the user explicitly said "USD" in the form
        if (
            isset($validated['currency_code'])
            && strtoupper($validated['currency_code']) === 'USD'
        ) {
            foreach ($fields as $fieldName) {
                if (!empty($validated[$fieldName])) {
                    // Use the amrshawky/laravel-currency package to convert
                    $validated[$fieldName] = Currency::convert()
                        ->from('USD')
                        ->to('EUR')
                        ->amount($validated[$fieldName])
                        ->get();
                }
            }
            // DO NOT change $validated['currency_code'];
            // We keep it as 'USD' per your request.
        }
    }

    /**
     * Price formula must use the final EUR publisher_price value.
     * For USD rows, triggers derive publisher_price from original_publisher_price * rate.
     */
    private function publisherPriceForPriceFormula(array $data): ?float
    {
        if (!array_key_exists('publisher_price', $data) || $data['publisher_price'] === null || $data['publisher_price'] === '') {
            return null;
        }

        $publisher = (float) $data['publisher_price'];
        if (strtoupper((string) ($data['currency_code'] ?? '')) !== 'USD') {
            return $publisher;
        }

        $baseUsd = $data['original_publisher_price'] ?? $data['publisher_price'];
        if ($baseUsd === null || $baseUsd === '') {
            return null;
        }

        return (float) $baseUsd * $this->usdEurRate();
    }

    private function usdEurRate(): float
    {
        static $rate = null;
        if ($rate !== null) {
            return $rate;
        }

        $rate = (float) DB::table('app_settings')
            ->where('setting_name', 'usd_eur_rate')
            ->value('setting_value');

        return $rate > 0 ? $rate : 1.0;
    }

    public function rollback(Request $request)
    {
        /* A) 4-second undo-toast */
        if ($request->filled('token')) {

            $token = $request->input('token');
            $snaps = RollbackWebsite::where('token', $token)->get();

            if ($snaps->isEmpty()) {
                return response()->json(['message' => 'Nothing to undo (expired)'], 404);
            }

            DB::transaction(function () use ($snaps) {
                foreach ($snaps as $snap) {
                    $row = Website::find($snap->website_id);
                    if (!$row) continue;

                    $row->fill($snap->snapshot['attributes'])->save();
                    $row->categories()->sync($snap->snapshot['categories']);
                }
                RollbackWebsite::whereIn('id', $snaps->pluck('id'))->delete();
            });

            return response()->json(['message' => 'Undo complete']);
        }

        /* B) Manual “Rollback” button */
        $ids = $request->input('ids', []);
        if (!is_array($ids) || !count($ids)) {
            return response()->json(['message' => 'No rows selected'], 422);
        }

        $snaps = RollbackWebsite::whereIn('website_id', $ids)
            ->latest()->get()->groupBy('website_id');

        DB::transaction(function () use ($snaps) {
            foreach ($snaps as $wid => $rows) {
                $snap = $rows->first();                 // newest
                $row = Website::find($wid);
                if (!$row) continue;

                $row->fill($snap->snapshot['attributes'])->save();
                $row->categories()->sync($snap->snapshot['categories']);
                RollbackWebsite::where('id', $snap->id)->delete();
            }
        });

        return response()->json(['message' => 'Rollback successful']);
    }
    /**
     * Validate form data for create/update.
     */
    protected function validateForm(Request $request)
    {
        return $request->validate([
            'domain_name'            => 'required|string|max:255',
            'status'                 => 'nullable|string|max:255',
            'country_id'             => 'nullable|integer',
            'contact_id'             => 'nullable|integer',
            'currency_code'          => 'nullable|string|max:255',
            'language_id'            => 'nullable|integer',
            'publisher_price'        => 'nullable|numeric',
            'date_publisher_price'   => 'nullable|date_format:d/m/Y',
            'link_insertion_price'   => 'nullable|numeric',
            'no_follow_price'        => 'nullable|numeric',
            'special_topic_price'    => 'nullable|numeric',
            'sensitive_topic_price'  => 'nullable|numeric',
           // 'profit'                 => 'nullable|numeric',
            'linkbuilder'            => 'nullable|string|max:255',
            //'automatic_evaluation'   => 'nullable|numeric',
            'kialvo_evaluation'      => 'nullable|numeric',
            'date_kialvo_evaluation' => 'nullable|date_format:d/m/Y',
            'type_of_website'        => 'nullable|string|max:255',
            'DA'                     => 'nullable|integer',
            'PA'                     => 'nullable|integer',
            'TF'                     => 'nullable|integer',
            'CF'                     => 'nullable|integer',
            'DR'                     => 'nullable|integer',
            'UR'                     => 'nullable|integer',
            'ZA'                     => 'nullable|integer',
            'as_metric'              => 'nullable|integer',
            'seozoom'                => 'nullable|string|max:255',
            'TF_vs_CF'               => 'nullable|numeric',
            'semrush_traffic'        => 'nullable|integer',
            'ahrefs_keyword'         => 'nullable|integer',
            'ahrefs_traffic'         => 'nullable|integer',
            'keyword_vs_traffic'     => 'nullable|numeric',
            'seo_metrics_date'       => 'nullable|date_format:d/m/Y',
            'betting'                => 'nullable|boolean',
            'trading'                => 'nullable|boolean',
            'permanent_link'                => 'nullable|boolean',
            'more_than_one_link'     => 'nullable|boolean',
            'copywriting'            => 'nullable|boolean',
            'no_sponsored_tag'       => 'nullable|boolean',
            'social_media_sharing'   => 'nullable|boolean',
            'post_in_homepage'       => 'nullable|boolean',
            'notes'                  => 'nullable|string',
            'extra_notes'            => 'nullable|string',
            'original_publisher_price'        => 'nullable|numeric',
            'original_no_follow_price'        => 'nullable|numeric',
            'original_link_insertion_price'        => 'nullable|numeric',
            'original_special_topic_price'        => 'nullable|numeric',
            'original_banner_price'        => 'nullable|numeric',
            'original_sitewide_link_price' => 'nullable|numeric',
            'banner_price'                 => 'nullable|numeric',
            'sitewide_link_price'          => 'nullable|numeric',
        ]);
    }
}
