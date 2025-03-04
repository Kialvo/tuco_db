<?php

namespace App\Http\Controllers;

use App\Models\Website;
use App\Models\Country;
use App\Models\Language;
use App\Models\Contact;
use App\Models\Category;
use Illuminate\Http\Request;
use Yajra\DataTables\DataTables;

class WebsiteController extends Controller
{
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
        $query = Website::with(['country','language','contact','categories']);

        // Apply filters – text search and numeric ranges.
        if (!empty($request->domain_name)) {
            $query->where('domain_name', 'like', '%'.$request->domain_name.'%');
        }

        if (!empty($request->type_of_website)) {
        $query->where('type_of_website', '=', $request->type_of_website);
         }

        if (!empty($request->status)) {

            $query->where('status', '=', $request->status);
        }

        if (!empty($request->publisher_price_min) && !empty($request->publisher_price_max)) {
            $query->whereBetween('publisher_price', [$request->publisher_price_min, $request->publisher_price_max]);
        } elseif (!empty($request->publisher_price_min)) {
            $query->where('publisher_price', '>=', $request->publisher_price_min);
        } elseif (!empty($request->publisher_price_max)) {
            $query->where('publisher_price', '<=', $request->publisher_price_max);
        }

// Kialvo Evaluation
        if (!empty($request->kialvo_min) && !empty($request->kialvo_max)) {
            $query->whereBetween('kialvo_evaluation', [$request->kialvo_min, $request->kialvo_max]);
        } elseif (!empty($request->kialvo_min)) {
            $query->where('kialvo_evaluation', '>=', $request->kialvo_min);
        } elseif (!empty($request->kialvo_max)) {
            $query->where('kialvo_evaluation', '<=', $request->kialvo_max);
        }

// Profit
        if (!empty($request->profit_min) && !empty($request->profit_max)) {
            $query->whereBetween('profit', [$request->profit_min, $request->profit_max]);
        } elseif (!empty($request->profit_min)) {
            $query->where('profit', '>=', $request->profit_min);
        } elseif (!empty($request->profit_max)) {
            $query->where('profit', '<=', $request->profit_max);
        }

        //Other numeric
        // DA (Domain Authority)
        if (!empty($request->DA_min) && !empty($request->DA_max)) {
            $query->whereBetween('DA', [$request->DA_min, $request->DA_max]);
        } elseif (!empty($request->DA_min)) {
            $query->where('DA', '>=', $request->DA_min);
        } elseif (!empty($request->DA_max)) {
            $query->where('DA', '<=', $request->DA_max);
        }

// PA (Page Authority)
        if (!empty($request->PA_min) && !empty($request->PA_max)) {
            $query->whereBetween('PA', [$request->PA_min, $request->PA_max]);
        } elseif (!empty($request->PA_min)) {
            $query->where('PA', '>=', $request->PA_min);
        } elseif (!empty($request->PA_max)) {
            $query->where('PA', '<=', $request->PA_max);
        }

// TF (Trust Flow)
        if (!empty($request->TF_min) && !empty($request->TF_max)) {
            $query->whereBetween('TF', [$request->TF_min, $request->TF_max]);
        } elseif (!empty($request->TF_min)) {
            $query->where('TF', '>=', $request->TF_min);
        } elseif (!empty($request->TF_max)) {
            $query->where('TF', '<=', $request->TF_max);
        }

// CF (Citation Flow)
        if (!empty($request->CF_min) && !empty($request->CF_max)) {
            $query->whereBetween('CF', [$request->CF_min, $request->CF_max]);
        } elseif (!empty($request->CF_min)) {
            $query->where('CF', '>=', $request->CF_min);
        } elseif (!empty($request->CF_max)) {
            $query->where('CF', '<=', $request->CF_max);
        }

        // TF_VS_CF
        if (!empty($request->TF_VS_CF_min) && !empty($request->TF_VS_CF_max)) {
            $query->whereBetween('TF_vs_CF', [$request->TF_VS_CF_min, $request->TF_VS_CF_max]);
        } elseif (!empty($request->CF_min)) {
            $query->where('TF_vs_CF', '>=', $request->TF_VS_CF_min);
        } elseif (!empty($request->CF_max)) {
            $query->where('TF_vs_CF', '<=', $request->TF_VS_CF_max);
        }

// DR (Domain Rating)
        if (!empty($request->DR_min) && !empty($request->DR_max)) {
            $query->whereBetween('DR', [$request->DR_min, $request->DR_max]);
        } elseif (!empty($request->DR_min)) {
            $query->where('DR', '>=', $request->DR_min);
        } elseif (!empty($request->DR_max)) {
            $query->where('DR', '<=', $request->DR_max);
        }

// UR (URL Rating)
        if (!empty($request->UR_min) && !empty($request->UR_max)) {
            $query->whereBetween('UR', [$request->UR_min, $request->UR_max]);
        } elseif (!empty($request->UR_min)) {
            $query->where('UR', '>=', $request->UR_min);
        } elseif (!empty($request->UR_max)) {
            $query->where('UR', '<=', $request->UR_max);
        }

// ZA (Zoom Authority)
        if (!empty($request->ZA_min) && !empty($request->ZA_max)) {
            $query->whereBetween('ZA', [$request->ZA_min, $request->ZA_max]);
        } elseif (!empty($request->ZA_min)) {
            $query->where('ZA', '>=', $request->ZA_min);
        } elseif (!empty($request->ZA_max)) {
            $query->where('ZA', '<=', $request->ZA_max);
        }

// SR (SEO Rank)
        if (!empty($request->SR_min) && !empty($request->SR_max)) {
            $query->whereBetween('as_metric', [$request->SR_min, $request->SR_max]);
        } elseif (!empty($request->SR_min)) {
            $query->where('as_metric', '>=', $request->SR_min);
        } elseif (!empty($request->SR_max)) {
            $query->where('as_metric', '<=', $request->SR_max);
        }

        if (!empty($request->SR_min) && !empty($request->SR_max)) {
            $query->whereBetween('as_metric', [$request->SR_min, $request->SR_max]);
        } elseif (!empty($request->SR_min)) {
            $query->where('as_metric', '>=', $request->SR_min);
        } elseif (!empty($request->SR_max)) {
            $query->where('as_metric', '<=', $request->SR_max);
        }

// Semrush Traffic
        if (!empty($request->semrush_traffic_min) && !empty($request->semrush_traffic_max)) {
            $query->whereBetween('semrush_traffic', [$request->semrush_traffic_min, $request->semrush_traffic_max]);
        } elseif (!empty($request->semrush_traffic_min)) {
            $query->where('semrush_traffic', '>=', $request->semrush_traffic_min);
        } elseif (!empty($request->semrush_traffic_max)) {
            $query->where('semrush_traffic', '<=', $request->semrush_traffic_max);
        }

// Ahrefs Keyword
        if (!empty($request->ahrefs_keyword_min) && !empty($request->ahrefs_keyword_max)) {
            $query->whereBetween('ahrefs_keyword', [$request->ahrefs_keyword_min, $request->ahrefs_keyword_max]);
        } elseif (!empty($request->ahrefs_keyword_min)) {
            $query->where('ahrefs_keyword', '>=', $request->ahrefs_keyword_min);
        } elseif (!empty($request->ahrefs_keyword_max)) {
            $query->where('ahrefs_keyword', '<=', $request->ahrefs_keyword_max);
        }

// Ahrefs Traffic
        if (!empty($request->ahrefs_traffic_min) && !empty($request->ahrefs_traffic_max)) {
            $query->whereBetween('ahrefs_traffic', [$request->ahrefs_traffic_min, $request->ahrefs_traffic_max]);
        } elseif (!empty($request->ahrefs_traffic_min)) {
            $query->where('ahrefs_traffic', '>=', $request->ahrefs_traffic_min);
        } elseif (!empty($request->ahrefs_traffic_max)) {
            $query->where('ahrefs_traffic', '<=', $request->ahrefs_traffic_max);
        }

// AH KW/TRAF (Ahrefs Keyword to Traffic Ratio)
        if (!empty($request->keyword_vs_traffic_min) && !empty($request->keyword_vs_traffic_max)) {
            $query->whereBetween('keyword_vs_traffic', [$request->keyword_vs_traffic_min, $request->keyword_vs_traffic_max]);
        } elseif (!empty($request->keyword_vs_traffic_min)) {
            $query->where('keyword_vs_traffic', '>=', $request->keyword_vs_traffic_min);
        } elseif (!empty($request->keyword_vs_traffic_max)) {
            $query->where('keyword_vs_traffic', '<=', $request->keyword_vs_traffic_max);
        }



        // Booleans – if the checkbox is checked (true), filter accordingly.
        if ($request->boolean('more_than_one_link')) {
            $query->where('more_than_one_link', true);
        }
        if ($request->boolean('copywriting')) {
            $query->where('copywriting', true);
        }
        if ($request->boolean('no_sponsored_tag')) {
            $query->where('no_sponsored_tag', true);
        }
        if ($request->boolean('social_media_sharing')) {
            $query->where('social_media_sharing', true);
        }
        if ($request->boolean('post_in_homepage')) {
            $query->where('post_in_homepage', true);
        }
        if ($request->boolean('betting')) {
            $query->where('betting', true);
        }
        if ($request->boolean('trading')) {
            $query->where('trading', true);
        }
        // If "show_deleted" is checked, restrict to onlyTrashed:
        if ($request->boolean('show_deleted')) {
            $query->onlyTrashed();

        }
        // SEO Metrics (examples: DA and PA; add more as needed)

        // Foreign key filters
        if (!empty($request->country_id)) {
            $query->where('country_id', $request->country_id);
        }
        if (!empty($request->language_id)) {
            $query->where('language_id', $request->language_id);
        }
        if (!empty($request->contact_id)) {
            $query->where('contact_id', $request->contact_id);
        }
        // Categories (multi-select)
        if (!empty($request->category_ids) && is_array($request->category_ids)) {
            $query->whereHas('categories', function($q) use ($request) {
                $q->whereIn('categories.id', $request->category_ids);
            });
        }


        // Use Yajra to transform the query
        return DataTables::of($query)

            ->addColumn('country_name', function($row){
                return $row->country ? $row->country->country_name : '';
            })
            ->addColumn('language_name', function($row){
                return $row->language ? $row->language->name : '';
            })
            ->addColumn('contact_name', function($row){
                return $row->contact ? $row->contact->name : '';
            })
            ->addColumn('categories_list', function($row){
                return $row->categories->pluck('name')->join(', ');
            })
            ->addColumn('action', function($row){ // Check if the record has an ID:
                $id = $row->id ?? null;
                if (!$id) {
                    return '';
                }

                // If this row is soft-deleted, we only show a “Restore” button
                if ($row->trashed()) {
                    $restoreUrl = route('websites.restore', $row->id);
                    return '
                    <form action="'.$restoreUrl.'" method="POST" style="display:inline;">
                        '.csrf_field().'
                        <button onclick="return confirm(\'Are you sure you want to restore this website?\')" class="text-green-600 underline">
                            Restore
                        </button>
                    </form>
                ';
                }


                $editUrl = route('websites.edit', $row->id);
                $deleteUrl = route('websites.destroy', $row->id);
                $showUrl = route('websites.show', $row->id);
                return '
                    <a href="'.$showUrl.'" class="text-green-600 underline mr-2">View</a>
                    <a href="'.$editUrl.'" class="text-blue-600 underline mr-2">Edit</a>
                    <form action="'.$deleteUrl.'" method="POST" style="display:inline;">
                        '.csrf_field().method_field("DELETE").'
                        <button onclick="return confirm(\'Are you sure?\')" class="text-red-600 underline">
                            Delete
                        </button>
                    </form>
                ';
            })
            ->rawColumns(['action'])
            ->make(true);
    }


    // ===============================
//  CSV EXPORT (All Fields)
// ===============================
    public function exportCsv(Request $request)
    {
        // 1) Build query with eager loads
        $query = Website::with(['country','language','contact','categories']);

        // 2) Apply the same filters (assuming you have applyFilters(...) method)
        $this->applyFilters($request, $query);

        // 3) Get the collection
        $websites = $query->get();

        // 4) Prepare CSV data
        //    The header row (all columns you want to export):
        $csvData = [];
        $csvData[] = [
            'ID',
            'Domain',
            'Publisher Price',
            'Kialvo',
            'Profit',
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
            'AS', // as_metric
            'SEO Zoom',
            'TF vs CF',
            'Semrush Traffic',
            'Ahrefs Keyword',
            'Ahrefs Traffic',
            'Keyword vs Traffic',
            'SEO Metrics Date',
            'Betting',
            'Trading',
            'More than 1 link',
            'Copywriting',
            'No Sponsored Tag',
            'Social Media Sharing',
            'Post in Homepage',
            'Date Added',
            'Extra Notes',
        ];

        // 5) Loop to fill each row
        foreach ($websites as $web) {
            $csvData[] = [
                $web->id,
                $web->domain_name,
                $web->publisher_price,
                $web->kialvo_evaluation,
                $web->profit,
                $web->DA,
                optional($web->country)->country_name,  // Safely handle null
                optional($web->language)->name,
                optional($web->contact)->name,
                // Categories as comma-separated list
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
                // Convert booleans to yes/no or just keep 0/1
                $web->betting ? 'Yes' : 'No',
                $web->trading ? 'Yes' : 'No',
                $web->more_than_one_link ? 'Yes' : 'No',
                $web->copywriting ? 'Yes' : 'No',
                $web->no_sponsored_tag ? 'Yes' : 'No',
                $web->social_media_sharing ? 'Yes' : 'No',
                $web->post_in_homepage ? 'Yes' : 'No',
                // 'created_at' as "Date Added"
                $web->created_at,
                $web->extra_notes,
            ];
        }

        // 6) Convert array -> CSV string
        $filename = 'websites_export_'.date('Y-m-d_His').'.csv';
        $handle   = fopen('php://temp', 'r+');
        foreach ($csvData as $row) {
            fputcsv($handle, $row);
        }
        rewind($handle);
        $csvOutput = stream_get_contents($handle);
        fclose($handle);

        // 7) Return as CSV download
        return response($csvOutput, 200, [
            'Content-Type'        => 'text/csv',
            'Content-Disposition' => "attachment; filename=\"$filename\"",
        ]);
    }


    protected function applyFilters(Request $request, $query)
    {

        if (!empty($request->domain_name)) {
            $query->where('domain_name', 'like', '%'.$request->domain_name.'%');
        }

        if (!empty($request->type_of_website)) {
            $query->where('type_of_website', '=', $request->type_of_website);
        }

        if (!empty($request->status)) {

            $query->where('status', '=', $request->status);
        }

        if (!empty($request->publisher_price_min) && !empty($request->publisher_price_max)) {
            $query->whereBetween('publisher_price', [$request->publisher_price_min, $request->publisher_price_max]);
        } elseif (!empty($request->publisher_price_min)) {
            $query->where('publisher_price', '>=', $request->publisher_price_min);
        } elseif (!empty($request->publisher_price_max)) {
            $query->where('publisher_price', '<=', $request->publisher_price_max);
        }

// Kialvo Evaluation
        if (!empty($request->kialvo_min) && !empty($request->kialvo_max)) {
            $query->whereBetween('kialvo_evaluation', [$request->kialvo_min, $request->kialvo_max]);
        } elseif (!empty($request->kialvo_min)) {
            $query->where('kialvo_evaluation', '>=', $request->kialvo_min);
        } elseif (!empty($request->kialvo_max)) {
            $query->where('kialvo_evaluation', '<=', $request->kialvo_max);
        }

// Profit
        if (!empty($request->profit_min) && !empty($request->profit_max)) {
            $query->whereBetween('profit', [$request->profit_min, $request->profit_max]);
        } elseif (!empty($request->profit_min)) {
            $query->where('profit', '>=', $request->profit_min);
        } elseif (!empty($request->profit_max)) {
            $query->where('profit', '<=', $request->profit_max);
        }

        //Other numeric
        // DA (Domain Authority)
        if (!empty($request->DA_min) && !empty($request->DA_max)) {
            $query->whereBetween('DA', [$request->DA_min, $request->DA_max]);
        } elseif (!empty($request->DA_min)) {
            $query->where('DA', '>=', $request->DA_min);
        } elseif (!empty($request->DA_max)) {
            $query->where('DA', '<=', $request->DA_max);
        }

// PA (Page Authority)
        if (!empty($request->PA_min) && !empty($request->PA_max)) {
            $query->whereBetween('PA', [$request->PA_min, $request->PA_max]);
        } elseif (!empty($request->PA_min)) {
            $query->where('PA', '>=', $request->PA_min);
        } elseif (!empty($request->PA_max)) {
            $query->where('PA', '<=', $request->PA_max);
        }

// TF (Trust Flow)
        if (!empty($request->TF_min) && !empty($request->TF_max)) {
            $query->whereBetween('TF', [$request->TF_min, $request->TF_max]);
        } elseif (!empty($request->TF_min)) {
            $query->where('TF', '>=', $request->TF_min);
        } elseif (!empty($request->TF_max)) {
            $query->where('TF', '<=', $request->TF_max);
        }

// CF (Citation Flow)
        if (!empty($request->CF_min) && !empty($request->CF_max)) {
            $query->whereBetween('CF', [$request->CF_min, $request->CF_max]);
        } elseif (!empty($request->CF_min)) {
            $query->where('CF', '>=', $request->CF_min);
        } elseif (!empty($request->CF_max)) {
            $query->where('CF', '<=', $request->CF_max);
        }

// DR (Domain Rating)
        if (!empty($request->DR_min) && !empty($request->DR_max)) {
            $query->whereBetween('DR', [$request->DR_min, $request->DR_max]);
        } elseif (!empty($request->DR_min)) {
            $query->where('DR', '>=', $request->DR_min);
        } elseif (!empty($request->DR_max)) {
            $query->where('DR', '<=', $request->DR_max);
        }

// UR (URL Rating)
        if (!empty($request->UR_min) && !empty($request->UR_max)) {
            $query->whereBetween('UR', [$request->UR_min, $request->UR_max]);
        } elseif (!empty($request->UR_min)) {
            $query->where('UR', '>=', $request->UR_min);
        } elseif (!empty($request->UR_max)) {
            $query->where('UR', '<=', $request->UR_max);
        }

// ZA (Zoom Authority)
        if (!empty($request->ZA_min) && !empty($request->ZA_max)) {
            $query->whereBetween('ZA', [$request->ZA_min, $request->ZA_max]);
        } elseif (!empty($request->ZA_min)) {
            $query->where('ZA', '>=', $request->ZA_min);
        } elseif (!empty($request->ZA_max)) {
            $query->where('ZA', '<=', $request->ZA_max);
        }

// SR (SEO Rank)
        if (!empty($request->SR_min) && !empty($request->SR_max)) {
            $query->whereBetween('SR', [$request->SR_min, $request->SR_max]);
        } elseif (!empty($request->SR_min)) {
            $query->where('SR', '>=', $request->SR_min);
        } elseif (!empty($request->SR_max)) {
            $query->where('SR', '<=', $request->SR_max);
        }

// Semrush Traffic
        if (!empty($request->semrush_traffic_min) && !empty($request->semrush_traffic_max)) {
            $query->whereBetween('semrush_traffic', [$request->semrush_traffic_min, $request->semrush_traffic_max]);
        } elseif (!empty($request->semrush_traffic_min)) {
            $query->where('semrush_traffic', '>=', $request->semrush_traffic_min);
        } elseif (!empty($request->semrush_traffic_max)) {
            $query->where('semrush_traffic', '<=', $request->semrush_traffic_max);
        }

// Ahrefs Keyword
        if (!empty($request->ahrefs_keyword_min) && !empty($request->ahrefs_keyword_max)) {
            $query->whereBetween('ahrefs_keyword', [$request->ahrefs_keyword_min, $request->ahrefs_keyword_max]);
        } elseif (!empty($request->ahrefs_keyword_min)) {
            $query->where('ahrefs_keyword', '>=', $request->ahrefs_keyword_min);
        } elseif (!empty($request->ahrefs_keyword_max)) {
            $query->where('ahrefs_keyword', '<=', $request->ahrefs_keyword_max);
        }

// Ahrefs Traffic
        if (!empty($request->ahrefs_traffic_min) && !empty($request->ahrefs_traffic_max)) {
            $query->whereBetween('ahrefs_traffic', [$request->ahrefs_traffic_min, $request->ahrefs_traffic_max]);
        } elseif (!empty($request->ahrefs_traffic_min)) {
            $query->where('ahrefs_traffic', '>=', $request->ahrefs_traffic_min);
        } elseif (!empty($request->ahrefs_traffic_max)) {
            $query->where('ahrefs_traffic', '<=', $request->ahrefs_traffic_max);
        }

// AH KW/TRAF (Ahrefs Keyword to Traffic Ratio)
        if (!empty($request->keyword_vs_traffic_min) && !empty($request->keyword_vs_traffic_max)) {
            $query->whereBetween('keyword_vs_traffic', [$request->keyword_vs_traffic_min, $request->keyword_vs_traffic_max]);
        } elseif (!empty($request->keyword_vs_traffic_min)) {
            $query->where('keyword_vs_traffic', '>=', $request->keyword_vs_traffic_min);
        } elseif (!empty($request->keyword_vs_traffic_max)) {
            $query->where('keyword_vs_traffic', '<=', $request->keyword_vs_traffic_max);
        }



        // Booleans – if the checkbox is checked (true), filter accordingly.
        if ($request->boolean('more_than_one_link')) {
            $query->where('more_than_one_link', true);
        }
        if ($request->boolean('copywriting')) {
            $query->where('copywriting', true);
        }
        if ($request->boolean('no_sponsored_tag')) {
            $query->where('no_sponsored_tag', true);
        }
        if ($request->boolean('social_media_sharing')) {
            $query->where('social_media_sharing', true);
        }
        if ($request->boolean('post_in_homepage')) {
            $query->where('post_in_homepage', true);
        }
        if ($request->boolean('betting')) {
            $query->where('betting', true);
        }
        if ($request->boolean('trading')) {
            $query->where('trading', true);
        }
        // If "show_deleted" is checked, restrict to onlyTrashed:
        if ($request->boolean('show_deleted')) {
            $query->onlyTrashed();

        }
        // SEO Metrics (examples: DA and PA; add more as needed)

        // Foreign key filters
        if (!empty($request->country_id)) {
            $query->where('country_id', $request->country_id);
        }
        if (!empty($request->language_id)) {
            $query->where('language_id', $request->language_id);
        }
        if (!empty($request->contact_id)) {
            $query->where('contact_id', $request->contact_id);
        }
        // Categories (multi-select)
        if (!empty($request->category_ids) && is_array($request->category_ids)) {
            $query->whereHas('categories', function($q) use ($request) {
                $q->whereIn('categories.id', $request->category_ids);
            });
        }

        return $query;
    }
    public function exportPdf(Request $request)
    {
        try {
            // First verify the view exists
            if (!view()->exists('websites.pdf')) {
                throw new \Exception('PDF template not found');
            }

            $query = Website::with(['country','language','contact','categories']);
            $this->applyFilters($request, $query);

            // Test with limited results first
            $websites = $query->get();

            // Test view rendering first
            $html = view('websites.pdf', compact('websites'))->render();
            \Log::info('HTML generated successfully');

            // Try smaller paper size
            $pdf = \PDF::loadHTML($html)
                ->setPaper('a1', 'landscape');

            return $pdf->download('test.pdf');
        } catch (\Exception $e) {
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
        $validated = $this->validateForm($request);

        // 2) Compute the automatic evaluation from your formula
        //    Formula: {DA}*2.4 + {TF}*1.45 + {DR}*0.5 + IF({SR}>=9700, {SR}/15000, 0)*1.35
        $da = $validated['DA'] ?? 0;
        $tf = $validated['TF'] ?? 0;
        $dr = $validated['DR'] ?? 0;
        // If your "SR" is stored in "as_metric", do:
        $sr = $validated['as_metric'] ?? 0;

        //dd($da,$tf,$dr);
        $autoEvaluation = ($da * 2.4) + ($tf * 1.45) + ($dr * 0.5);


        if ($sr >= 9700) {
            $autoEvaluation += ($sr / 15000) * 1.35;
        }

        // 3) Override / set 'automatic_evaluation' in the $validated array
        $validated['automatic_evaluation'] = $autoEvaluation;


        // 4) Create the new Website using the final data
        $website = Website::create($validated);

        // If you have categories
        if ($request->has('category_ids')) {
            $website->categories()->sync($request->category_ids);
        }

        return redirect()->route('websites.index')
            ->with('status', 'Website created successfully!');
    }


    /**
     * Display a single website.
     */
    public function show(Website $website)
    {
        $website->load(['country', 'language', 'contact', 'categories']);
        return view('websites.show', compact('website'));
    }

    /**
     * Show the edit form.
     */
    public function edit(Website $website)
    {
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
        // 1) Validate the incoming data
        $validated = $this->validateForm($request);

        // 2) Compute automatic evaluation
        $da = $validated['DA'] ?? 0;
        $tf = $validated['TF'] ?? 0;
        $dr = $validated['DR'] ?? 0;
        $sr = $validated['as_metric'] ?? 0;

        $autoEvaluation = ($da * 2.4) + ($tf * 1.45) + ($dr * 0.5);

        if ($sr >= 9700) {
            $autoEvaluation += ($sr / 15000) * 1.35;
        }

        $validated['automatic_evaluation'] = $autoEvaluation;
        dd($validated);
        // 3) Update the record
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
            'date_publisher_price'   => 'nullable|date',
            'link_insertion_price'   => 'nullable|numeric',
            'no_follow_price'        => 'nullable|numeric',
            'special_topic_price'    => 'nullable|numeric',
            'profit'                 => 'nullable|numeric',
            'linkbuilder'            => 'nullable|string|max:255',
            //'automatic_evaluation'   => 'nullable|numeric',
            'kialvo_evaluation'      => 'nullable|numeric',
            'date_kialvo_evaluation' => 'nullable|date',
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
            'seo_metrics_date'       => 'nullable|date',
            'betting'                => 'nullable|boolean',
            'trading'                => 'nullable|boolean',
            'more_than_one_link'     => 'nullable|boolean',
            'copywriting'            => 'nullable|boolean',
            'no_sponsored_tag'       => 'nullable|boolean',
            'social_media_sharing'   => 'nullable|boolean',
            'post_in_homepage'       => 'nullable|boolean',
            'extra_notes'            => 'nullable|string',
        ]);
    }
}
