<?php

namespace App\Http\Controllers;

use AmrShawky\Currency\Facade\Currency;
use App\Models\RollbackStorage;
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
use Illuminate\Support\Facades\DB;
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
        'copy_nr','copywriter_commision_date','copywriter_submission_date',
        // PUBLISHER
        'publisher_currency','publisher_amount',
        // PRICES & COSTS
        'publisher','total_cost','menford','client_copy','total_revenues','profit',
        // CAMPAIGN & LINKS
        'campaign','anchor_text','target_url','campaign_code',
        // PUBLICATION
        'article_sent_to_publisher','publication_date','expiration_date','article_url',
        // INVOICING / PAYMENTS
        'method_payment_to_us','invoice_menford','invoice_menford_nr','invoice_company',
        'payment_to_us_date','bill_publisher_name','bill_publisher_nr','bill_publisher_date',
        'payment_to_publisher_date','method_payment_to_publisher','category_ids',
        // FILES & NOTES
        'files','extra_notes',
        self::FIELD_RECALC,
    ];

    private const FIELD_RECALC = 'recalculate_totals';
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
            ->editColumn('publisher_period', fn ($r) => (int) $r->publisher_period)
            ->editColumn('created_at',       fn ($r) =>
            $r->created_at?->format('Y/m/d'))   // ← NEW
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
| AJAX  →  aggregate numbers for the summary row
|======================================================================*/
    public function summary(Request $request)
    {
        $cols = [
            'copy_nr',
            'copywriter_period',
            'publisher_amount',
            'publisher',
            'total_cost',
            'menford',
            'client_copy',
            'total_revenues',
            'profit',
            'publisher_period',
        ];

        $query = Storage::query();

        // A. If specific IDs are selected, use only them
        if ($request->filled('ids') && is_array($request->ids)) {
            $query->whereIn('id', $request->ids);
        } else {
            // B. Otherwise fall back to filters
            $this->applyFilters($request, $query);
        }

        $agg = [];
        $rows = $query->get($cols); // only fetch numeric columns

        foreach ($cols as $c) {
            $vec = $rows->pluck($c)->filter(fn($v) => $v !== null)->values();

            $agg[$c] = [
                'sum'    => $vec->sum(),
                'average'    => $vec->isEmpty() ? 0 : $vec->avg(),
                'median' => $vec->median(),
                'min'    => $vec->min(),
                'max'    => $vec->max(),
                'count'  => $vec->count(),
            ];
        }

        return response()->json($agg);
    }

    public function bulkUpdate(Request $request)
    {
        /* -------- validation -------- */
        $data = $request->validate([
            'ids'   => 'required|array|min:1',
            'ids.*' => 'integer|exists:storage,id',
            'field' => ['required','string',function($a,$v,$f){
                if (!in_array($v, self::BULK_EDITABLE, true)) {
                    $f('Field not allowed for bulk edit.');
                }
            }],
            'value' => 'sometimes',
        ]);

        $field = $data['field'];
        $value = $request->input('value', null);
        if ($value === '') $value = null;                       // normalise

        $token = Str::uuid();                                   // for undo

        DB::transaction(function () use ($data, $field, &$value, $token) {

            /* 1. snapshots --------------------------------------------------- */
            $rows = Storage::with('categories')
                ->whereIn('id', $data['ids'])
                ->get();

            foreach ($rows as $row) {
                RollbackStorage::create([
                    'token'      => $token,
                    'storage_id' => $row->id,
                    'snapshot'   => [
                        'attributes' => $row->getAttributes(),
                        'categories' => $row->categories->pluck('id')->all(),
                    ],
                ]);
            }

            /* 2. special action – just recalc ------------------------------- */
            if ($field === self::FIELD_RECALC) {
                foreach ($rows as $s) {
                    $payload = $s->getAttributes();             // live row
                    $this->applyAutoCalculations($payload);      // mutate array

                    $s->fill([
                        'total_cost'        => $payload['total_cost'],
                        'total_revenues'    => $payload['total_revenues'],
                        'profit'            => $payload['profit'],
                        'copywriter_period' => $payload['copywriter_period'] ?? $s->copywriter_period,
                        'publisher_period'  => $payload['publisher_period']  ?? $s->publisher_period,
                    ])->save();
                }
                return;                                         // done
            }

            /* 3. normal bulk update ----------------------------------------- */
            /*   (currency change is NOT a driver, so no auto-recalc here)     */
            $drivers = [
                'copy_nr','publisher_amount','menford','client_copy',
                'copywriter_commision_date','copywriter_submission_date',
                'article_sent_to_publisher','publication_date',
            ];

            /* normalise dates once */
            if (Str::endsWith($field, '_date') && $value !== null) {
                $value = preg_match('#^\d{2}/\d{2}/\d{4}$#', $value)
                    ? Carbon::createFromFormat('d/m/Y', $value)->toDateString()
                    : Carbon::parse($value)->toDateString();
            }

            foreach ($rows as $s) {
                $s->{$field} = $value;

                if (in_array($field, $drivers, true)) {
                    $payload = array_merge($s->getAttributes(), [$field => $value]);
                    $this->applyAutoCalculations($payload);

                    $s->fill([
                        'total_cost'        => $payload['total_cost'],
                        'total_revenues'    => $payload['total_revenues'],
                        'profit'            => $payload['profit'],
                        'copywriter_period' => $payload['copywriter_period'] ?? $s->copywriter_period,
                        'publisher_period'  => $payload['publisher_period']  ?? $s->publisher_period,
                    ]);
                }
                $s->save();
            }
        });

        return response()->json([
            'message'    => 'Updated '.count($data['ids']).' record(s).',
            'undo_token' => $token,
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

        foreach (self::DATE_COLS as $c) {
            $validated[$c] = $this->euDate($validated[$c] ?? null);
        }
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

        foreach (self::DATE_COLS as $c) {
            $validated[$c] = $this->euDate($validated[$c] ?? null);
        }

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
        $token = Str::uuid();
        RollbackStorage::create([
            'token'      => $token,
            'storage_id' => $storage->id,
            'snapshot'   => [
                'attributes'=>$storage->getAttributes(),
                'categories'=>$storage->categories->pluck('id')->all()
            ]
        ]);


        $storage->delete();
        return back()->with('status', 'Storage soft-deleted!');
    }

    public function undo(Request $request)
    {
        $token = $request->input('token');
        if(!$token){ return response()->json(['message'=>'Missing token'],422); }

        $items = RollbackStorage::where('token',$token)->get();
        if($items->isEmpty()){
            return response()->json(['message'=>'Nothing to undo'],422);
        }

        DB::transaction(function() use ($items){
            foreach ($items as $snap) {
                /* ---- restore attributes ---- */
                $store = Storage::withTrashed()->find($snap->storage_id);
                if(!$store){ continue; }

                $store->fill($snap->snapshot['attributes']);
                $store->save();

                /* ---- restore categories ---- */
                $store->categories()->sync($snap->snapshot['categories']);
            }
            // remove snapshots so we don’t restore twice
            RollbackStorage::whereIn('id',$items->pluck('id'))->delete();
        });

        return response()->json(['message'=>'Undo successful']);
    }

    public function rollback(Request $request)
    {
        /* ── A) Undo-button in toast  → we get  {token: "..."} ───────────── */
        if ($request->filled('token')) {

            $token = $request->input('token');

            $snaps = RollbackStorage::where('token', $token)->get();

            if ($snaps->isEmpty()) {
                return response()->json(['message' => 'Nothing to undo (expired)'], 404);
            }

            DB::transaction(function() use ($snaps) {

                foreach ($snaps as $snap) {

                    /** @var \App\Models\Storage $row */
                    $row   = Storage::find($snap->storage_id);

                    if (!$row) {                       // row could have been deleted
                        continue;
                    }

                    /* restore every column */
                    $row->fill($snap->snapshot['attributes']);
                    $row->save();

                    /* restore many-to-many categories */
                    $row->categories()->sync($snap->snapshot['categories']);
                }

                /* delete snapshots so the same token can’t be reused */
                RollbackStorage::whereIn('id', $snaps->pluck('id'))->delete();
            });

            return response()->json(['message' => 'Undo complete']);
        }

        /* ── B) Manual “Rollback” button  → we get  ids[] ─────────────────── */
        $ids = $request->input('ids', []);
        if (!is_array($ids) || !count($ids)) {
            return response()->json(['message' => 'No rows selected'], 422);
        }

        $snaps = RollbackStorage::whereIn('storage_id', $ids)
            ->latest()            // newest snapshot per storage
            ->get()
            ->groupBy('storage_id');

        DB::transaction(function() use ($snaps) {

            foreach ($snaps as $storageId => $rows) {

                $snap = $rows->first();               // newest one

                /** @var \App\Models\Storage $row */
                $row = Storage::find($storageId);
                if (!$row) { continue; }

                $row->fill($snap->snapshot['attributes']);
                $row->save();
                $row->categories()->sync($snap->snapshot['categories']);

                RollbackStorage::where('id', $snap->id)->delete();
            }
        });

        return response()->json([
            'message' => 'Rollback successful'
        ]);
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
            'copywriter_commision_date'   => 'nullable|date_format:d/m/Y',
            'copywriter_submission_date'  => 'nullable|date_format:d/m/Y',
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
            'article_sent_to_publisher'   => 'nullable|date_format:d/m/Y',
            'publication_date'            => 'nullable|date_format:d/m/Y',
            'expiration_date'             => 'nullable|date_format:d/m/Y',
            'publisher_period'            => 'nullable|numeric',
            'article_url'                 => 'nullable|url|max:255',
            'method_payment_to_us'        => 'nullable|string|max:255',
            'invoice_menford'             => 'nullable|date_format:d/m/Y',
            'invoice_menford_nr'          => 'nullable|string|max:255',
            'invoice_company'             => 'nullable|string|max:255',
            'payment_to_us_date'          => 'nullable|date_format:d/m/Y',
            'publisher_article'           => 'nullable|numeric',
            'bill_publisher_name'         => 'nullable|string|max:255',
            'bill_publisher_nr'           => 'nullable|string|max:255',
            'bill_publisher_date'         => 'nullable|date_format:d/m/Y',        //  ←  added
            'payment_to_publisher_date'   => 'nullable|date_format:d/m/Y',
            'method_payment_to_publisher' => 'nullable|string|max:255',
            'files'                       => 'nullable|string',
            'category_ids'                => 'nullable|array',
            'category_ids.*'              => 'integer'
        ]);
    }

    /** every column that stores a date (no timestamps) */
    private const DATE_COLS = [
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

    /** 18/06/2025  →  2025-06-18   (returns null if empty) */
    private function euDate(?string $v): ?string
    {
        if (!$v) return null;
        try   { return Carbon::createFromFormat('d/m/Y', $v)->format('Y-m-d'); }
        catch (\Throwable $e) { return $v; }         // let validation complain if needed
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
        /* ---------------- prices / profit -------------------------------- */
        $publisher = (float) ($data['publisher_amount'] ?? $data['publisher'] ?? 0);
        $copywriterAmount = (float) ($data['copy_nr']      ?? 0);
        $data['total_cost'] = $publisher + $copywriterAmount;

        $menford    = (float) ($data['menford']     ?? 0);
        $clientCopy = (float) ($data['client_copy'] ?? 0);
        $data['total_revenues'] = $menford + $clientCopy;

        $data['profit'] = $data['total_revenues'] - $data['total_cost'];

        /* ---------------- Copy period  (submission − commission) --------- */
        if (!empty($data['copywriter_commision_date']) && !empty($data['copywriter_submission_date'])) {
            $from = Carbon::parse($data['copywriter_commision_date']);
            $to   = Carbon::parse($data['copywriter_submission_date']);
            $data['copywriter_period'] = $from->diffInDays($to);     // int
        }

        /* ---------------- Publisher period  (publication − sent) --------- */
        if (!empty($data['article_sent_to_publisher']) && !empty($data['publication_date'])) {
            $from = Carbon::parse($data['article_sent_to_publisher']);
            $to   = Carbon::parse($data['publication_date']);
            $data['publisher_period'] = $from->diffInDays($to);      // int
        }
    }
}
