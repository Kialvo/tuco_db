<?php

namespace App\Http\Controllers;

use AmrShawky\Currency\Facade\Currency;
use App\Models\Storage;
use App\Models\Country;
use App\Models\Language;
use App\Models\Client;
use App\Models\Copy;
use App\Models\Category;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Yajra\DataTables\DataTables;

class StorageController extends Controller
{
    /*======================================================================
    | INDEX – filters + DataTable view
    ======================================================================*/
    public function index()
    {
        $countries  = Country::all();
        $languages  = Language::all();
        $clients    = Client::all();
        $categories = Category::all();
        $copies     = Copy::all();

        return view('storages.index', compact(
            'countries',
            'languages',
            'clients',
            'copies',
            'categories'
        ));
    }

    /*======================================================================
    | DATATABLES JSON
    ======================================================================*/
    /*======================================================================
| DATATABLES JSON
======================================================================*/
    public function getData(Request $request)
    {
        $query = Storage::with([
            'site:id,domain_name',
            'country:id,country_name',
            'language:id,name',
            'client:id,first_name,last_name',
            'copy:id,copy_val',
            'categories:id,name'
        ]);

        /* ---------- filters ---------- */
        // publication date range
        if ($request->filled('publication_from') && $request->filled('publication_to')) {
            $query->whereBetween('publication_date', [$request->publication_from, $request->publication_to]);
        } elseif ($request->filled('publication_from')) {
            $query->where('publication_date', '>=', $request->publication_from);
        } elseif ($request->filled('publication_to')) {
            $query->where('publication_date', '<=', $request->publication_to);
        }

        // simple FK / scalar filters
        if ($request->filled('copy_id'))            $query->where('copy_id',            $request->copy_id);
        if ($request->filled('language_id'))        $query->where('language_id',        $request->language_id);
        if ($request->filled('country_id'))         $query->where('country_id',         $request->country_id);
        if ($request->filled('client_id'))          $query->where('client_id',          $request->client_id);
        if ($request->filled('status'))             $query->where('status',             $request->status);

        // LIKE-based text filters
        if ($request->filled('campaign'))           $query->where('campaign',           'like', '%'.$request->campaign.'%');            // Target Domain
        if ($request->filled('campaign_code'))      $query->where('campaign_code',      'like', '%'.$request->campaign_code.'%');
        if ($request->filled('invoice_menford_nr')) $query->where('invoice_menford_nr', 'like', '%'.$request->invoice_menford_nr.'%');
        if ($request->filled('bill_publisher_name'))$query->where('bill_publisher_name','like', '%'.$request->bill_publisher_name.'%');
        if ($request->filled('target_url'))         $query->where('target_url',         'like', '%'.$request->target_url.'%');
        if ($request->filled('article_url'))        $query->where('article_url',        'like', '%'.$request->article_url.'%');

        // categories (multi-select)
        if ($request->filled('category_ids') && is_array($request->category_ids)) {
            $query->whereHas('categories', fn ($q) => $q->whereIn('categories.id', $request->category_ids));
        }

        // soft-deleted toggle
        if ($request->boolean('show_deleted')) $query->onlyTrashed();

        /* ---------- DataTables ---------- */
        return DataTables::of($query)
            ->addColumn('website_domain',    fn ($r) => optional($r->site)->domain_name)
            ->addColumn('country_name',      fn ($r) => optional($r->country)->country_name)
            ->addColumn('language_name',     fn ($r) => optional($r->language)->name)
            ->addColumn('client_name', function ($r) {
                return $r->client ? trim($r->client->first_name.' '.$r->client->last_name) : '';
            })
            ->addColumn('copywriter_name',   fn ($r) => optional($r->copy)->copy_val ?? '')
            ->editColumn('copywriter_period',fn ($r) => (int) $r->copywriter_period)
            ->addColumn('categories_list',   fn ($r) => $r->categories->pluck('name')->join(', '))
            ->addColumn('action', function ($r) {
                if ($r->trashed()) {
                    $restoreUrl = route('storages.restore', $r->id);
                    return '<form action="'.$restoreUrl.'" method="POST" style="display:inline;">'
                        .csrf_field().
                        '<button onclick="return confirm(\'Restore?\')" class="inline-flex items-center bg-green-600 text-white px-3 py-1 rounded">
                            <i class="fas fa-undo-alt mr-1"></i>Restore
                        </button></form>';
                }

                $edit = route('storages.edit', $r->id);
                $del  = route('storages.destroy', $r->id);

                return '<a href="'.$edit.'" class="inline-flex items-center bg-cyan-600 text-white px-3 py-1 rounded mr-1">
                        <i class="fas fa-pen mr-1"></i>Edit
                    </a>'
                    .'<form action="'.$del.'" method="POST" style="display:inline-block;">'
                    .csrf_field().method_field('DELETE').
                    '<button onclick="return confirm(\'Delete?\')" class="inline-flex items-center bg-red-600 text-white px-3 py-1 rounded">
                        <i class="fas fa-trash mr-1"></i>Delete
                    </button></form>';
            })
            ->rawColumns(['action'])
            ->make(true);
    }


    /*======================================================================
    | CREATE / STORE
    ======================================================================*/
    public function create()
    {
        $countries  = Country::all();
        $languages  = Language::all();
        $clients    = Client::all();
        $copies     = Copy::all();
        $categories = Category::all();

        return view('storages.create', compact(
            'countries',
            'languages',
            'clients',
            'copies',
            'categories'
        ));
    }

    public function store(Request $request)
    {
        $validated = $this->validateForm($request);

        $this->convertUsdFieldsToEur($validated, $this->priceFields());

        $this->applyAutoCalculations($validated);

        $storage = Storage::create($validated);

        if ($request->has('category_ids')) {
            $storage->categories()->sync($request->category_ids);
        }

        return redirect()
            ->route('storages.edit', $storage)
            ->with('status', 'Storage created.');
    }

    /*======================================================================
    | SHOW / EDIT / UPDATE
    ======================================================================*/
    public function show(Storage $storage)
    {
        $storage->load(['website','country','language','client','copy','categories']);
        return view('storages.show', compact('storage'));
    }

    public function edit(Storage $storage)
    {
        $storage->load(['site','country','language','client','copy','categories']);
        $countries  = Country::all();
        $languages  = Language::all();
        $clients    = Client::all();
        $copies     = Copy::all();
        $categories = Category::all();

        return view('storages.edit', compact(
            'storage',
            'countries',
            'languages',
            'clients',
            'copies',
            'categories'
        ));
    }

    public function update(Request $request, Storage $storage)
    {
        $validated = $this->validateForm($request);

        $this->convertUsdFieldsToEur($validated, $this->priceFields());

        $this->applyAutoCalculations($validated);

        $storage->update($validated);

        if ($request->has('category_ids')) {
            $storage->categories()->sync($request->category_ids);
        } else {
            $storage->categories()->sync([]);
        }

        return redirect()
            ->route('storages.index')
            ->with('status', 'Storage updated.');
    }

    /*======================================================================
    | DESTROY / RESTORE
    ======================================================================*/
    public function destroy(Storage $storage)
    {
        $storage->delete();
        return back()->with('status', 'Storage soft-deleted!');
    }

    public function restore($id)
    {
        Storage::onlyTrashed()->findOrFail($id)->restore();
        return back()->with('status', 'Storage restored!');
    }

    /*======================================================================
| EXPORT CSV (with field selection)
======================================================================*/
    public function exportCsv(Request $request)
    {
        // 1) Filter + fetch
        $query    = Storage::with(['site','country','language','client','copy','categories']);
        $this->applyFilters($request, $query);
        $storages = $query->get();

        // 2) Determine fields
        $allKeys = array_merge(['id'], array_keys($this->csvRow($storages->first() ?? new Storage())));
        $fields  = $request->input('fields', $allKeys);
        if (! in_array('id', $fields, true)) {
            array_unshift($fields, 'id');
        }

        // 3) Build CSV
        $filename = 'storages_'.now()->format('Y-m-d_His').'.csv';
        $handle   = fopen('php://temp','r+');
        fputcsv($handle, array_map([Str::class,'headline'],$fields));
        foreach ($storages as $s) {
            $assoc = $this->csvRow($s);
            $row   = [];
            foreach ($fields as $f) {
                $row[] = $f==='id' ? $s->id : ($assoc[$f] ?? '');
            }
            fputcsv($handle, $row);
        }
        rewind($handle);
        $csv = stream_get_contents($handle);
        fclose($handle);

        return response($csv, 200, [
            'Content-Type'        => 'text/csv',
            'Content-Disposition' => "attachment; filename=\"$filename\"",
        ]);
    }


    /*======================================================================
    | EXPORT PDF (with field selection)
    ======================================================================*/
    public function exportPdf(Request $request)
    {
        Log::info('[storages.exportPdf] begin');
        try {
            // 1) Filter + fetch
            $query    = Storage::with(['site','country','language','client','copy','categories']);
            $this->applyFilters($request, $query);
            $storages = $query->get();
            Log::info('[storages.exportPdf] count='.$storages->count());

            // 2) Fields selection
            $allKeys = array_merge(['id'], array_keys($this->csvRow($storages->first() ?? new Storage())));
            $fields  = $request->input('fields', $allKeys);
            if (! in_array('id', $fields, true)) {
                array_unshift($fields, 'id');
            }
            Log::info('[storages.exportPdf] fields='.implode(',',$fields));

            // 3) Build header & rows
            $header = collect($fields)->map(fn($f)=> Str::headline($f))->all();
            $rows   = [];
            foreach ($storages as $s) {
                $assoc = $this->csvRow($s);
                $row   = [];
                foreach ($fields as $f) {
                    $row[] = $f === 'id' ? $s->id : ($assoc[$f] ?? '');
                }
                $rows[] = $row;
            }

            // 4) Render & download
            $pdf = PDF::loadView('storages.pdf', compact('header','rows'))
                ->setPaper('a4','landscape');
            Log::info('[storages.exportPdf] success');
            return $pdf->download('storages_'.now()->format('Y-m-d_His').'.pdf');
        }
        catch (\Exception $e) {
            Log::error('[storages.exportPdf] '.$e->getMessage());
            Log::error($e->getTraceAsString());
            abort(500,'PDF generation failed; check logs.');
        }
    }




    /*======================================================================
    | Helpers
    ======================================================================*/
    /**
     * Extracted filter logic so we can reuse it in DataTables, CSV & PDF exports.
     */
    protected function applyFilters(Request $request, $query)
    {
        // Publication date range
        if ($request->filled('publication_from') && $request->filled('publication_to')) {
            $query->whereBetween('publication_date', [$request->publication_from, $request->publication_to]);
        } elseif ($request->filled('publication_from')) {
            $query->where('publication_date', '>=', $request->publication_from);
        } elseif ($request->filled('publication_to')) {
            $query->where('publication_date', '<=', $request->publication_to);
        }

        // FK & status filters
        if ($request->filled('copy_id'))       $query->where('copy_id',       $request->copy_id);
        if ($request->filled('language_id'))   $query->where('language_id',   $request->language_id);
        if ($request->filled('country_id'))    $query->where('country_id',    $request->country_id);
        if ($request->filled('client_id'))     $query->where('client_id',     $request->client_id);
        if ($request->filled('status'))        $query->where('status',        $request->status);

        // LIKE filters
        if ($request->filled('campaign'))           $query->where('campaign',           'like', '%'.$request->campaign.'%');
        if ($request->filled('campaign_code'))      $query->where('campaign_code',      'like', '%'.$request->campaign_code.'%');
        if ($request->filled('invoice_menford_nr')) $query->where('invoice_menford_nr', 'like', '%'.$request->invoice_menford_nr.'%');
        if ($request->filled('bill_publisher_name'))$query->where('bill_publisher_name', 'like', '%'.$request->bill_publisher_name.'%');
        if ($request->filled('target_url'))         $query->where('target_url',         'like', '%'.$request->target_url.'%');
        if ($request->filled('article_url'))        $query->where('article_url',        'like', '%'.$request->article_url.'%');

        // Categories multi‐select
        if ($request->filled('category_ids') && is_array($request->category_ids)) {
            $query->whereHas('categories', fn($q)=> $q->whereIn('categories.id', $request->category_ids));
        }

        // Soft‐deletes toggle
        if ($request->boolean('show_deleted')) {
            $query->onlyTrashed();
        }

        return $query;
    }


    private function validateForm(Request $r)
    {
        return $r->validate([
            'website_id'                  => 'nullable|integer',
            'status'                      => 'nullable|string|max:255',
            'LB'                          => 'nullable|string|max:255',
            'client_id'                   => 'nullable|integer',
            'copy_id'                     => 'nullable|integer',
            'copy_nr'                     => 'nullable|numeric',
            'copywriter_commision_date'   => 'nullable|date',
            'copywriter_submission_date'  => 'nullable|date',
            'copywriter_period'           => 'nullable|numeric',
            'language_id'                 => 'nullable|integer',
            'country_id'                  => 'nullable|integer',
            'publisher_currency'          => 'nullable|string|max:255',
            'publisher_amount'            => 'nullable|numeric',
            'publisher'                   => 'nullable|numeric',
            'total_cost'                  => 'nullable|numeric',
            'menford'                     => 'nullable|numeric',
            'client_copy'                 => 'nullable|numeric',
            'total_revenues'              => 'nullable|numeric',
            'profit'                      => 'nullable|numeric',
            'campaign'                    => 'nullable|string|max:255',
            'anchor_text'                 => 'nullable|string',
            'target_url'                  => 'nullable|url|max:255',
            'campaign_code'               => 'nullable|string|max:255',
            'article_sent_to_publisher'   => 'nullable|date',
            'publication_date'            => 'nullable|date',
            'expiration_date'             => 'nullable|date',
            'publisher_period'            => 'nullable|numeric',
            'article_url'                 => 'nullable|url|max:255',
            'method_payment_to_us'        => 'nullable|string|max:255',
            'invoice_menford'             => 'nullable|date',
            'invoice_menford_nr'          => 'nullable|string|max:255',
            'invoice_company'             => 'nullable|string|max:255',
            'payment_to_us_date'          => 'nullable|date',
            'publisher_article'           => 'nullable|numeric',
            'bill_publisher_name'         => 'nullable|string|max:255',
            'bill_publisher_nr'           => 'nullable|string|max:255',
            'bill_publisher_date'         => 'nullable|date',        //  ←  added
            'payment_to_publisher_date'   => 'nullable|date',
            'method_payment_to_publisher' => 'nullable|string|max:255',
            'files'                       => 'nullable|string',
            'category_ids'                => 'nullable|array',
            'category_ids.*'              => 'integer'
        ]);
    }

    private function priceFields(): array
    {
        return [
            'publisher',
            'total_cost',
            'menford',
            'client_copy',
            'total_revenues',
            'profit',
            'publisher_article'
        ];
    }

    private function convertUsdFieldsToEur(array &$d, array $fields): void
    {
        if (isset($d['currency_code']) && strtoupper($d['currency_code']) === 'USD') {
            foreach ($fields as $f) {
                if (isset($d[$f]) && $d[$f] !== null) {
                    $d[$f] = Currency::convert()
                        ->from('USD')->to('EUR')
                        ->amount($d[$f])->get();
                }
            }
        }
    }


    private function csvRow(Storage $s): array
    {
        // 1) Grab all the “raw” DB columns
        $row = $s->only([
            'website_id',
            'status',
            'LB',
            'client_id',
            'copy_id',
            'copy_nr',
            'copywriter_commision_date',
            'copywriter_submission_date',
            'copywriter_period',
            'language_id',
            'country_id',
            'publisher',
            'total_cost',
            'menford',
            'client_copy',
            'total_revenues',
            'profit',
            'campaign',
            'anchor_text',
            'target_url',
            'campaign_code',
            'article_sent_to_publisher',
            'publication_date',
            'expiration_date',
            'publisher_period',
            'article_url',
            'method_payment_to_us',
            'invoice_menford',
            'invoice_menford_nr',
            'invoice_company',
            'payment_to_us_date',
            'publisher_article',
            'bill_publisher_name',
            'bill_publisher_nr',
            'bill_publisher_date',
            'payment_to_publisher_date',
            'method_payment_to_publisher',
            'files',
        ]);

        // 2) Re‐format all date fields to DD-MM-YYYY, stripping off any time
        $dateFields = [
            'copywriter_commision_date',
            'copywriter_submission_date',
            'article_sent_to_publisher',
            'publication_date',
            'expiration_date',
            'invoice_menford',
            'payment_to_us_date',
            'bill_publisher_date',
            'payment_to_publisher_date',
        ];
        foreach ($dateFields as $f) {
            if (! empty($row[$f])) {
                $row[$f] = Carbon::parse($row[$f])->format('d-m-Y');
            }
        }

        // 3) Inject the “computed” display columns
        $row['id']               = $s->id;
        $row['website_domain']   = optional($s->site)->domain_name;
        $row['client_name']      = $s->client
            ? trim($s->client->first_name . ' ' . $s->client->last_name)
            : '';
        $row['copywriter_name']  = optional($s->copy)->copy_val ?? '';
        $row['language_name']    = optional($s->language)->name ?? '';
        $row['country_name']     = optional($s->country)->country_name ?? '';
        $row['categories_list']  = $s->categories->pluck('name')->join(', ');

        return $row;
    }




    /*======================================================================
    | NEW: automatic calculations & clean-up
    ======================================================================*/
    private function applyAutoCalculations(array &$data): void
    {
        if (isset($data['copywriter_period'])) {
            $data['copywriter_period'] = (int) $data['copywriter_period'];
        }

        $publisher        = (float) ($data['publisher']    ?? 0);
        $copywriterAmount = (float) ($data['copy_nr']      ?? 0);
        $data['total_cost'] = $publisher + $copywriterAmount;

        $menford    = (float) ($data['menford']     ?? 0);
        $clientCopy = (float) ($data['client_copy'] ?? 0);
        $data['total_revenues'] = $menford + $clientCopy;

        $data['profit'] = $data['total_revenues'] - $data['total_cost'];
    }
}
