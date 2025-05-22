<?php

namespace App\Http\Controllers;

use AmrShawky\Currency\Facade\Currency;
use App\Models\Storage;
use App\Models\Country;
use App\Models\Language;
use App\Models\Client;
use App\Models\Copy;
use App\Models\Category;
use App\Models\Website;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Yajra\DataTables\DataTables;

class StorageController extends Controller
{

    /**
     * All columns that may be updated in bulk.
     * Feel free to remove any you do NOT want to expose.
     */
    public const BULK_EDITABLE = [
        // GENERAL / FK
        'status','LB','client_id','copy_id','country_id','language_id',
        // COPY DETAILS
        'copy_nr','copywriter_commision_date','copywriter_submission_date','copywriter_period',
        // PUBLISHER
        'publisher_currency','publisher_amount',
        // PRICES & COSTS
        'publisher','total_cost','menford','client_copy','total_revenues','profit',
        // CAMPAIGN & LINKS
        'campaign','anchor_text','target_url','campaign_code',
        // PUBLICATION
        'article_sent_to_publisher','publication_date','expiration_date','publisher_period','article_url',
        // INVOICING / PAYMENTS
        'method_payment_to_us','invoice_menford','invoice_menford_nr','invoice_company',
        'payment_to_us_date','bill_publisher_name','bill_publisher_nr','bill_publisher_date',
        'payment_to_publisher_date','method_payment_to_publisher','category_ids',
        // FILES & NOTES
        'files','extra_notes',
    ];

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
            'categories',

        ));
    }

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


    /**
     * Update the same field for many rows at once.
     * POST:  ids[] , field , value
     */
//    public function bulkUpdate(Request $request)
//    {
//        $data = $request->validate([
//            'ids'   => 'required|array|min:1',
//            'ids.*' => 'integer|exists:storage,id',
//            'field' => ['required','string',function($attr,$val,$fail){
//                if (! in_array($val, self::BULK_EDITABLE, true)) {
//                    $fail('Field not allowed for bulk edit.');
//                }
//            }],
//            'value' => 'nullable|string|max:255',
//        ]);
//
//        $field = $data['field'];
//        $value = $data['value'];
//
//        /* Normalise DD-MM-YYYY → YYYY-MM-DD for *_date fields */
//        /* Normalise dates -----------------------------------------------------*/
//        if (Str::endsWith($field, '_date') && $value !== '') {
//
//            // try the two common day-first / year-first formats
//            $dt = null;
//            foreach (['Y-m-d', 'd-m-Y'] as $fmt) {
//                try {
//                    $dt = Carbon::createFromFormat($fmt, $value);
//                    break;           // stop at the first that parses
//                } catch (\Throwable $e) { /* keep trying */ }
//            }
//
//            if (! $dt) {                      // none matched
//                return response()->json(['message' => 'Invalid date format'], 422);
//            }
//
//            $value = $dt->toDateString();       //  YYYY-MM-DD 00:00:00
//        }
//
//
//        Storage::whereIn('id',$data['ids'])->update([$field=>$value]);
//
//        return response()->json(['message'=>"Updated ".count($data['ids'])." record(s)."]);
//    }

    /**
     * One field → many rows.
     * Request:  ids[] , field , value
     */
//    public function bulkUpdate(Request $request)
//    {
//        /* --------- 1. validation --------- */
//        $data = $request->validate([
//            'ids'   => 'required|array|min:1',
//            'ids.*' => 'integer|exists:storage,id',
//            'field' => ['required','string',function($a,$v,$f){
//                if (! in_array($v, self::BULK_EDITABLE, true)) {
//                    $f('Field not allowed for bulk-edit.');
//                }
//            }],
//            'value' => 'nullable|string|max:255',
//        ]);
//
//        $field = $data['field'];
//        $value = $data['value'];
//
//        /* --------- 2. normalise dates --------- */
//        if (Str::endsWith($field,'_date') && $value !== '') {
//
//            $dt = null;
//            foreach (['d-m-Y','Y-m-d'] as $fmt) {          // day-first & year-first
//                try { $dt = Carbon::createFromFormat($fmt,$value); break; }
//                catch (\Throwable $e) { /* keep trying */ }
//            }
//            if (! $dt) {
//                return response()->json(['message'=>'Invalid date format'],422);
//            }
//            $value = $dt->toDateString();                  // YYYY-MM-DD
//        }
//
//        /* --------- 3. update + auto-recalc --------- */
//        $recalcDrivers = ['copy_nr','publisher','menford','client_copy'];
//
//        // pull all affected rows at once
//        $storages = Storage::whereIn('id',$data['ids'])->get();
//
//        foreach ($storages as $s) {
//
//            /* 3.1 set the user-chosen value */
//            $s->{$field} = $value;
//
//            /* 3.2 if the edited field influences totals, recompute them */
//            if (in_array($field,$recalcDrivers, true)) {
//
//                // put current attributes in an array, run the same helper
//                $payload = $s->attributesToArray();
//                $payload[$field] = is_numeric($value) ? (float)$value : $value;
//
//                $this->applyAutoCalculations($payload);
//
//                $s->publisher       = $payload['publisher'];
//                $s->total_cost      = $payload['total_cost'];
//                $s->menford         = $payload['menford'];
//                $s->client_copy     = $payload['client_copy'];
//                $s->total_revenues  = $payload['total_revenues'];
//                $s->profit          = $payload['profit'];
//            }
//
//            $s->save();
//        }
//
//        return response()->json([
//            'message' => 'Updated '.count($storages).' record(s).'
//        ]);
//    }
    /**
     * One field → many rows.
     * Request:  ids[] , field , value
     */
    /**
     * Bulk-edit: update one column on many rows at once.
     * Expects:  POST  ids[] , field , value
     */
    /**
     * Bulk-edit: update one column on many rows at once.
     * Accepts empty value (clears column) and multiple date formats.
     */
    /**
     * Bulk-edit: update the same column on many rows.
     * – blank value (“”)   ⇒ NULL
     * – dates are accepted in several formats, otherwise left NULL
     */
    /**
     * Bulk-edit: update one column on many rows.
     * – blank (“”) is saved as NULL
     * – dates accepted in common formats
     * – category_ids syncs the pivot table
     */
    public function bulkUpdate(Request $request)
    {
        /* ---------- 1. basic validation ------------------------------------ */
        $fieldRule = ['required','string', function ($attr,$val,$fail) {
            if (! in_array($val, self::BULK_EDITABLE, true)) {
                $fail('Field not allowed for bulk edit.');
            }
        }];

        $data = $request->validate([
            'ids'   => 'required|array|min:1',
            'ids.*' => 'integer|exists:storage,id',
            'field' => $fieldRule,
            'value' => 'nullable',                           // may be "", null, array …
        ]);

        $field = $data['field'];
        $value = $data['value'];                            // raw value from AJAX

        /* ---------- 2. special case: categories --------------------------- */
        if ($field === 'category_ids') {

            // value could be "" (user pressed “Clear”), turn into empty array
            $ids = is_array($value) ? $value : [];

            Storage::whereIn('id', $data['ids'])->each(function ($s) use ($ids) {
                $s->categories()->sync($ids);               // replace the set
            });

            return response()->json([
                'message' => 'Categories updated for '.count($data['ids']).' record(s).'
            ]);
        }

        /* ---------- 3. generic scalar / date columns ---------------------- */
        if ($value === '') {                    // empty string clears the column
            $value = null;
        }

        if (Str::endsWith($field, '_date') && $value !== null) {
            $formats = ['Y-m-d','d-m-Y','m-d-Y','Y/m/d','d/m/Y','m/d/Y'];
            $parsed  = null;

            foreach ($formats as $fmt) {
                try { $parsed = Carbon::createFromFormat($fmt,$value); break; }
                catch (\Throwable $e) { /* keep trying */ }
            }
            if (! $parsed) {                     // give Carbon one free shot
                try { $parsed = Carbon::parse($value); } catch (\Throwable $e) {}
            }
            if (! $parsed) {
                return response()->json(['message'=>'Invalid date format'],422);
            }
            $value = $parsed->toDateString();    // YYYY-MM-DD
        }

        /* ---------- 4. update + (optional) auto-totals -------------------- */
        $drivers = ['copy_nr','publisher','menford','client_copy'];
        $rows    = Storage::whereIn('id',$data['ids'])->get();

        foreach ($rows as $s) {
            $s->{$field} = $value;

            if (in_array($field,$drivers,true)) {
                $payload = array_merge($s->toArray(), [$field=>$value]);
                $this->applyAutoCalculations($payload);
                $s->total_cost     = $payload['total_cost'];
                $s->total_revenues = $payload['total_revenues'];
                $s->profit         = $payload['profit'];
            }
            $s->save();
        }

        return response()->json([
            'message' => 'Updated '.count($rows).' record(s).'
        ]);
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
        $websites   = Website::orderBy('domain_name')->get();
        return view('storages.create', compact(
            'countries',
            'languages',
            'clients',
            'copies',
            'categories',
            'websites'
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
        $websites   = Website::orderBy('domain_name')->get();

        return view('storages.edit', compact(
            'storage',
            'countries',
            'languages',
            'clients',
            'copies',
            'categories'
            ,'websites'
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
        try {
            // 1) fetch
            $query    = Storage::with(['site','country','language','client','copy','categories']);
            $this->applyFilters($request, $query);
            $storages = $query->get();

            // 2) fields
            $allKeys = array_keys($this->csvRow($storages->first() ?? new Storage()));
            $fields  = $request->input('fields', $allKeys);

            // 3) header + rows for the Blade view
            $header = collect($fields)->map(fn ($f) => Str::headline($f))->all();
            $rows   = [];
            foreach ($storages as $s) {
                $assoc = $this->csvRow($s);
                $rows[] = collect($fields)->map(fn ($f) => $assoc[$f] ?? '')->all();
            }

            // 4) generate PDF – create /resources/views/storages/pdf.blade.php
            $pdf = PDF::loadView('storages.pdf', compact('header', 'rows'))
                ->setPaper('a1', 'landscape');

            return $pdf->download('storages_'.now()->format('Y-m-d_His').'.pdf');
        } catch (\Throwable $e) {
            Log::error('[storages.exportPdf] '.$e->getMessage());
            abort(500, 'PDF generation failed – check logs.');
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


    /**
     * Build one ordered row for CSV (and PDF) export, with dates in d-m-Y format.
     */
    private function csvRow(Storage $s): array
    {
        // 1) Grab & format all date fields
        $raw = $s->only([
            'copywriter_commision_date',
            'copywriter_submission_date',
            'article_sent_to_publisher',
            'publication_date',
            'expiration_date',
            'invoice_menford',
            'payment_to_us_date',
            'bill_publisher_date',
            'payment_to_publisher_date',
        ]);

        foreach ($raw as $key => $val) {
            $raw[$key] = $val
                ? Carbon::parse($val)->format('d-m-Y')
                : '';
        }

        // 2) Return one associative array in exactly the order of your <th> definitions
        return [
            'id'                          => $s->id,
            'website_domain'              => optional($s->site)->domain_name,
            'status'                       => $s->status,
            'LB'                           => $s->LB,
            'client_name'                 => $s->client
                ? trim($s->client->first_name . ' ' . $s->client->last_name)
                : '',
            'copywriter_name'             => optional($s->copy)->copy_val ?? '',
            'copy_nr'                     => $s->copy_nr,
            'copywriter_commision_date'   => $raw['copywriter_commision_date'],
            'copywriter_submission_date'  => $raw['copywriter_submission_date'],
            'copywriter_period'           => $s->copywriter_period,
            'language_name'               => optional($s->language)->name ?? '',
            'country_name'                => optional($s->country)->country_name ?? '',
            'publisher_currency'          => $s->publisher_currency,
            'publisher_amount'            => $s->publisher_amount,
            'publisher'                   => $s->publisher,
            'total_cost'                  => $s->total_cost,
            'menford'                     => $s->menford,
            'client_copy'                 => $s->client_copy,
            'total_revenues'              => $s->total_revenues,
            'profit'                      => $s->profit,
            'campaign'                    => $s->campaign,
            'anchor_text'                 => $s->anchor_text,
            'target_url'                  => $s->target_url,
            'campaign_code'               => $s->campaign_code,
            'article_sent_to_publisher'   => $raw['article_sent_to_publisher'],
            'publication_date'            => $raw['publication_date'],
            'expiration_date'             => $raw['expiration_date'],
            'publisher_period'            => $s->publisher_period,
            'article_url'                 => $s->article_url,
            'method_payment_to_us'        => $s->method_payment_to_us,
            'invoice_menford'             => $raw['invoice_menford'],
            'invoice_menford_nr'          => $s->invoice_menford_nr,
            'invoice_company'             => $s->invoice_company,
            'payment_to_us_date'          => $raw['payment_to_us_date'],
            'bill_publisher_name'         => $s->bill_publisher_name,
            'bill_publisher_nr'           => $s->bill_publisher_nr,
            'bill_publisher_date'         => $raw['bill_publisher_date'],
            'payment_to_publisher_date'   => $raw['payment_to_publisher_date'],
            'method_payment_to_publisher' => $s->method_payment_to_publisher,
            'categories_list'             => $s->categories->pluck('name')->join(', '),
            'files'                       => $s->files,
        ];
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
