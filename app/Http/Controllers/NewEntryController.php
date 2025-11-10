<?php

namespace App\Http\Controllers;

use App\Models\NewEntry;
use App\Models\RollbackNewEntry;
// use App\Models\RollbackWebsite;   // ← removed
use App\Models\Website;
use App\Models\Category;
use App\Models\Country;
use App\Models\Language;
use App\Models\Contact;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Yajra\DataTables\DataTables;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use AmrShawky\Currency\Facade\Currency;

class NewEntryController extends Controller
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
        'date_kialvo_evaluation',
        'first_contact_date',
        // BOOLEAN FLAGS
        'betting','trading','permanent_link','more_than_one_link',
        'copywriting','no_sponsored_tag','social_media_sharing','post_in_homepage',
        'category_ids',
        self::FIELD_RECALC,
    ];

    public const FIELD_RECALC = 'recalculate_totals';

    /** Recalc drivers */
    private const DRIVER_COLS = [
        'publisher_price','banner_price','sitewide_link_price',
        'kialvo_evaluation','ahrefs_keyword','ahrefs_traffic',
    ];

    /* =========================================================
     * LIST + DATATABLE
     * =======================================================*/
    public function index()
    {
        $countries  = Country::all();
        $languages  = Language::all();
        $contacts   = Contact::all();
        $categories = Category::all();

        return view('new_entries.index', compact(
            'countries','languages','contacts','categories'
        ));
    }

    public function getData(Request $r)
    {
        $q = NewEntry::with(['country', 'language', 'contact', 'categories']);

        if ($v = $r->domain_name) $q->where('domain_name', 'like', "%$v%");
        if ($v = $r->status)      $q->where('status', $v);
        if ($v = $r->country_ids) $q->where('country_id', $v);
        if ($v = $r->language_id) $q->where('language_id', $v);

        if ($ids = $r->category_ids) {
            $ids = is_array($ids) ? $ids : explode(',', $ids);
            $q->whereHas('categories', fn ($x) => $x->whereIn('categories.id', $ids));
        }

        $from = $r->first_contact_from;
        $to   = $r->first_contact_to;
        $isDate = fn($s) => is_string($s) && preg_match('/^\d{4}-\d{2}-\d{2}$/', $s);
        if ($isDate($from) && $isDate($to)) {
            $q->whereBetween('first_contact_date', [$from, $to]);
        } elseif ($isDate($from)) {
            $q->whereDate('first_contact_date', '>=', $from);
        } elseif ($isDate($to)) {
            $q->whereDate('first_contact_date', '<=', $to);
        }

        if ($r->boolean('research_mode')) {
            $q->whereNot(function ($x) {
                $x->whereIn('status', ['negotiation','active','publisher_refused','refused_by_us'])
                    ->orWhere(function ($y) {
                        $y->where('status', 'waiting_for_first_answer')
                            ->whereDate('first_contact_date', '>', now()->subDays(15));
                    });
            });
        }

        if ($r->boolean('show_deleted')) {
            $q->onlyTrashed();
        }

        return DataTables::of($q)
            ->addColumn('country_name',    fn ($r) => optional($r->country)->country_name)
            ->addColumn('language_name',   fn ($r) => optional($r->language)->name)
            ->addColumn('contact_name',    fn ($r) => optional($r->contact)->name)
            ->addColumn('categories_list', fn ($r) => $r->categories->pluck('name')->join(', '))
            ->addColumn('action', function ($row) {
                if ($row->trashed()) {
                    $restoreUrl = route('new_entries.restore', $row->id);
                    return '
                        <form action="'.$restoreUrl.'" method="POST" style="display:inline;">
                            '.csrf_field().'
                            <button
                                onclick="return confirm(\'Are you sure you want to restore this entry?\')"
                                class="inline-flex items-center bg-green-600 text-white px-3 py-1 rounded shadow-sm
                                       hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500">
                                <i class="fas fa-undo-alt mr-1"></i> Restore
                            </button>
                        </form>';
                }

                $viewUrl   = route('new_entries.show',    $row->id);
                $editUrl   = route('new_entries.edit',    $row->id);
                $deleteUrl = route('new_entries.destroy', $row->id);

                return '
                <div class="inline-flex space-x-1">
                    <a href="'.$viewUrl.'"
                       class="inline-flex items-center bg-green-600 text-white px-3 py-1 rounded shadow-sm
                              hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500">
                        <i class="fas fa-eye mr-1"></i> View
                    </a>
                    <a href="'.$editUrl.'"
                       class="inline-flex items-center bg-cyan-600 text-white px-3 py-1 rounded shadow-sm
                              hover:bg-cyan-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-cyan-500">
                        <i class="fas fa-pen mr-1"></i> Edit
                    </a>
                    <form action="'.$deleteUrl.'" method="POST" style="display:inline-block;">
                        '.csrf_field().method_field("DELETE").'
                        <button
                            onclick="return confirm(\'Are you sure you want to delete this entry?\')"
                            class="inline-flex items-center bg-red-600 text-white px-3 py-1 rounded shadow-sm
                                   hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500">
                            <i class="fas fa-trash mr-1"></i> Delete
                        </button>
                    </form>
                </div>';
            })
            ->rawColumns(['action'])
            ->make(true);
    }

    /* =========================================================
     * INLINE STATUS UPDATE (single-row)
     * =======================================================*/
    public function updateStatus(Request $r, NewEntry $new_entry)
    {
        $r->validate(['status'=>'required|string|max:255']);

        $old = strtolower((string) $new_entry->status);
        $new = strtolower((string) $r->status);

        DB::transaction(function () use ($new_entry, $new, $old) {
            $new_entry->status = $new;
            $new_entry->save();

            if ($old !== 'active' && $new === 'active') {
                $this->moveToWebsites($new_entry); // single-row copy
            }
        });

        return response()->json(['ok'=>true]);
    }

    /* =========================================================
     * CRUD
     * =======================================================*/
    public function create() { return view('new_entries.create', $this->lookups()); }

    public function store(Request $r)
    {
        $data = $this->validateForm($r);

        foreach (['first_contact_date','date_publisher_price','date_kialvo_evaluation','seo_metrics_date'] as $f) {
            $data[$f] = $this->euDate($data[$f] ?? null);
        }

        $this->recalcArray($data);

        DB::transaction(function () use ($data, $r, &$entry) {
            $entry = NewEntry::create($data);
            $entry->categories()->sync($r->category_ids ?? []);

            if (strtolower((string) $entry->status) === 'active') {
                $this->moveToWebsites($entry);
            }
        });

        return redirect()->route('new_entries.edit', $entry->id)
            ->with('status', 'Entry created – you can now complete / review it!');
    }

    public function edit(NewEntry $new_entry)
    {
        $new_entry->load('categories');
        return view('new_entries.edit', array_merge(['entry'=>$new_entry], $this->lookups()));
    }

    public function update(Request $r, NewEntry $new_entry)
    {
        $data = $this->validateForm($r);

        foreach (['first_contact_date','date_publisher_price','date_kialvo_evaluation','seo_metrics_date'] as $f) {
            $data[$f] = $this->euDate($data[$f] ?? null);
        }

        $this->recalcArray($data);

        $wasActive = strtolower((string) $new_entry->status) === 'active';

        DB::transaction(function () use ($data, $r, $new_entry, $wasActive) {
            $new_entry->fill($data)->save();
            $new_entry->categories()->sync($r->category_ids ?? []);

            $nowActive = strtolower((string) $new_entry->status) === 'active';
            if (!$wasActive && $nowActive) {
                $this->moveToWebsites($new_entry);
            }
        });

        return redirect()->route('new_entries.index')
            ->with('status', 'Entry updated!');
    }

    public function show(NewEntry $new_entry)
    {
        $new_entry->load(['country', 'language', 'contact', 'categories']);
        return view('new_entries.show', compact('new_entry'));
    }

    public function destroy(NewEntry $new_entry)
    {
        $new_entry->delete();
        return back()->with('status','Entry deleted (soft).');
    }

    /* =========================================================
     * BULK UPDATE  (with Website copy; UNDO only touches NewEntry)
     * =======================================================*/
    public function bulkUpdate(Request $request)
    {
        $data = $request->validate([
            'ids'   => 'required',
            'field' => ['required','string', function($a,$v,$f){
                if (!in_array($v, self::BULK_EDITABLE, true)) $f('Field not allowed for bulk edit.');
            }],
            'value' => 'sometimes',
        ]);

        // ids → array<int>
        $ids = is_array($request->ids)
            ? array_values($request->ids)
            : array_filter(array_map('intval', explode(',', (string)$request->ids)));

        if (empty($ids)) {
            return response()->json(['message' => 'No valid ids provided'], 422);
        }

        $field = $data['field'];
        $value = $request->input('value', null);
        if ($value === '') $value = null;

        $token = (string) Str::uuid(); // UNDO token (NewEntry only)

        DB::transaction(function () use ($ids, $field, &$value, $token) {
            $rows = NewEntry::with('categories')->whereIn('id', $ids)->get();

            // 1) snapshot for rollback (NewEntry only)
            foreach ($rows as $row) {
                RollbackNewEntry::create([
                    'token'        => $token,
                    'new_entry_id' => $row->id,
                    'snapshot'     => [
                        'attributes' => $row->getAttributes(),
                        'categories' => $row->categories->pluck('id')->all(),
                    ],
                ]);
            }

            // 2) pseudo-field: recalc derived KPIs
            if ($field === self::FIELD_RECALC) {
                foreach ($rows as $w) {
                    $payload = $w->getAttributes();
                    $this->applyAutoCalculations($payload);
                    $w->fill([
                        'profit'              => $payload['profit'],
                        'total_cost'          => $payload['total_cost'],
                        'total_revenues'      => $payload['total_revenues'],
                        'keyword_vs_traffic'  => $payload['keyword_vs_traffic'],
                        'TF_vs_CF'            => $payload['TF_vs_CF'],
                    ])->save();
                }
                return;
            }

            // 3) m2m categories
            if ($field === 'category_ids') {
                $catIds = is_array($value) ? array_filter($value)
                    : array_filter(explode(',', (string) $value));
                foreach ($rows as $w) {
                    $w->categories()->sync($catIds);
                }
                return;
            }

            // 4) normalize yyyy-mm-dd (or dd/mm/yyyy) → Y-m-d
            if ((Str::endsWith($field, '_date') || Str::startsWith($field, 'date_')) && $value !== null) {
                $value = preg_match('#^\d{2}/\d{2}/\d{4}$#', $value)
                    ? Carbon::createFromFormat('d/m/Y', $value)->toDateString()
                    : Carbon::parse($value)->toDateString();
            }

            // 5) scalar bulk updates + recalc drivers + bulk copy when status → active
            foreach ($rows as $w) {
                $oldStatus = strtolower((string) $w->status);

                // USD handling for price fields
                $priceFields = [
                    'publisher_price','link_insertion_price','no_follow_price','special_topic_price',
                    'banner_price','sitewide_link_price',
                ];

                if (in_array($field, $priceFields, true)) {
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
                        if ($origField) $w->{$origField} = $value;

                        try {
                            $converted = Currency::convert()
                                ->from('USD')->to('EUR')->amount((float) $value)->get();
                            $w->{$field} = $converted;
                        } catch (\Throwable $e) {
                            $w->{$field} = $value;
                        }
                    } else {
                        $w->{$field} = $value;
                    }
                } else {
                    $w->{$field} = $value;
                }

                // Recalc when driver changes
                if (in_array($field, self::DRIVER_COLS, true)) {
                    $payload = array_merge($w->getAttributes(), [$field=>$value]);
                    $this->applyAutoCalculations($payload);
                    $w->fill([
                        'profit'             => $payload['profit'],
                        'total_cost'         => $payload['total_cost'],
                        'total_revenues'     => $payload['total_revenues'],
                        'keyword_vs_traffic' => $payload['keyword_vs_traffic'],
                        'TF_vs_CF'           => $payload['TF_vs_CF'],
                    ]);
                }

                $w->save();

                // 6) BULK COPY: when status becomes active (no rollback in Websites)
                if ($field === 'status') {
                    $newStatus = strtolower((string) $value);
                    if ($oldStatus !== 'active' && $newStatus === 'active') {
                        try { $this->moveToWebsites($w); } catch (\Throwable $e) { /* keep going */ }
                    }
                }
            }
        });

        return response()->json([
            'message'    => 'Updated '.count($ids).' record(s).',
            'undo_token' => $token,
        ]);
    }

    /**
     * UNDO (by token) or Restore selection (by ids).
     * → Only revert NewEntries (do NOT touch Websites).
     */
    public function rollback(Request $r)
    {
        $r->validate([
            'token' => 'nullable|string',
            'ids'   => 'nullable',
        ]);

        // Undo whole bulk by token
        if ($token = $r->input('token')) {
            DB::transaction(function () use ($token) {
                $snaps = RollbackNewEntry::where('token',$token)->orderByDesc('id')->get();
                foreach ($snaps as $snap) {
                    $attrs = (array) ($snap->snapshot['attributes'] ?? []);
                    $cats  = (array) ($snap->snapshot['categories'] ?? []);

                    $entry = NewEntry::withTrashed()->find($snap->new_entry_id);
                    if (!$entry) continue;

                    $entry->forceFill($attrs);
                    $entry->save();
                    try { $entry->categories()->sync($cats); } catch (\Throwable $e) {}
                }

                // purge snapshots for this token
                RollbackNewEntry::where('token',$token)->delete();
            });

            return response()->json(['message' => 'Changes undone.']);
        }

        // Restore latest snapshot per selected id
        $ids = $r->input('ids', []);
        if (!is_array($ids)) $ids = explode(',', (string)$ids);
        $ids = array_filter(array_map('intval', $ids));

        if (empty($ids)) {
            return response()->json(['message'=>'Nothing to restore'], 422);
        }

        DB::transaction(function () use ($ids) {
            foreach ($ids as $id) {
                $snap = RollbackNewEntry::where('new_entry_id',$id)->orderByDesc('id')->first();
                if (!$snap) continue;

                $attrs = (array) ($snap->snapshot['attributes'] ?? []);
                $cats  = (array) ($snap->snapshot['categories'] ?? []);

                $entry = NewEntry::withTrashed()->find($id);
                if (!$entry) continue;

                $entry->forceFill($attrs);
                $entry->save();
                try { $entry->categories()->sync($cats); } catch (\Throwable $e) {}
            }
        });

        return response()->json(['message'=>'Restored previous snapshot for selected rows.']);
    }

    /* =========================================================
     * HELPERS
     * =======================================================*/

    /** Copy to Websites; returns Website (created or existing) or null. */
    private function moveToWebsites(NewEntry $e): ?Website
    {
        // If a Website already exists for this domain, mark copied and skip create
        $existing = Website::where('domain_name', $e->domain_name)->first();
        if ($existing) {
            $e->updateQuietly([
                'copied_to_overview' => 1,
                'date_added'         => Carbon::now(),
            ]);
            // keep categories synced (idempotent)
            $existing->categories()->sync($e->categories()->pluck('categories.id'));
            return $existing;
        }

        // Copy only model-fillable attributes
        $data = $e->only((new Website)->getFillable());

        // normalize dates dd/mm/yyyy -> Y-m-d
        foreach (['date_publisher_price','date_kialvo_evaluation','seo_metrics_date'] as $f) {
            if (!empty($data[$f])) {
                try { $data[$f] = Carbon::createFromFormat('d/m/Y', $data[$f])->format('Y-m-d'); }
                catch (\Throwable $ignored) {}
            }
        }

        // fields influenced by DB trigger
        $triggerFields = [
            'publisher_price','link_insertion_price','no_follow_price',
            'special_topic_price','banner_price','sitewide_link_price',
            'profit','automatic_evaluation',
        ];

        // If USD, pre-divide to neutralize BEFORE INSERT conversion
        if (!empty($data['currency_code']) && strtoupper($data['currency_code']) === 'USD') {
            $rate = (float) DB::table('app_settings')
                ->where('setting_name','usd_eur_rate')
                ->value('setting_value');

            if ($rate > 0) {
                foreach ($triggerFields as $f) {
                    if (isset($data[$f]) && $data[$f] !== '' && $data[$f] !== null) {
                        $val = (float) str_replace(',', '.', $data[$f]);
                        $data[$f] = round($val / $rate, 8);
                    }
                }
            }
        }

        // ensure original_* mirrors
        $map = [
            'publisher_price'        => 'original_publisher_price',
            'link_insertion_price'   => 'original_link_insertion_price',
            'no_follow_price'        => 'original_no_follow_price',
            'special_topic_price'    => 'original_special_topic_price',
            'banner_price'           => 'original_banner_price',
            'sitewide_link_price'    => 'original_sitewide_link_price',
        ];
        foreach ($map as $field => $original) {
            if (empty($data[$original]) && isset($data[$field])) {
                $data[$original] = $data[$field];
            }
        }

        // Create website
        $website = Website::create($data);

        // categories
        $website->categories()->sync($e->categories->pluck('id'));

        // mark as copied
        $e->updateQuietly([
            'copied_to_overview' => 1,
            'date_added'         => Carbon::now(),
        ]);

        return $website;
    }

    /** derive metrics for array payload (used in bulk + CSV import) */
    private function applyAutoCalculations(array &$d): void
    {
        $cost  = (float) ($d['publisher_price'] ?? 0);
        $rev   = (float) ($d['kialvo_evaluation'] ?? 0)
            + (float) ($d['banner_price'] ?? 0)
            + (float) ($d['sitewide_link_price'] ?? 0);

        $d['profit']         = $rev - $cost;
        $d['total_cost']     = $cost;
        $d['total_revenues'] = $rev;

        $kw = (float) ($d['ahrefs_keyword'] ?? 0);
        $tr = (float) ($d['ahrefs_traffic'] ?? 0);
        $d['keyword_vs_traffic'] = $tr > 0 ? round($kw / $tr, 2) : 0;

        $tf = (float) ($d['TF'] ?? 0);
        $cf = (float) ($d['CF'] ?? 0);
        $d['TF_vs_CF'] = $cf ? round($tf / $cf, 2) : 0;
    }

    private function lookups(): array
    {
        return [
            'countries'  => Country::all(),
            'languages'  => Language::all(),
            'contacts'   => Contact::all(),
            'categories' => Category::all(),
        ];
    }

    private function validateForm(Request $r): array
    {
        return $r->validate([
            'domain_name' => 'required|string|max:255',
            'status'      => 'nullable|string|max:255',
            'linkbuilder' => 'nullable|string|max:255',
            'country_id'  => 'nullable|integer',
            'language_id' => 'nullable|integer',
            'contact_id'  => 'nullable|integer',
            'currency_code'=>'nullable|string|max:10',
            'type_of_website' => 'nullable|string|max:255',

            'publisher_price'=>'nullable|numeric','link_insertion_price'=>'nullable|numeric',
            'no_follow_price'=>'nullable|numeric','special_topic_price'=>'nullable|numeric',
            'banner_price'=>'nullable|numeric','sitewide_link_price'=>'nullable|numeric',

            'original_publisher_price'=>'nullable|numeric',
            'original_link_insertion_price'=>'nullable|numeric',
            'original_no_follow_price'=>'nullable|numeric',
            'original_special_topic_price'=>'nullable|numeric',
            'original_banner_price'=>'nullable|numeric',
            'original_sitewide_link_price'=>'nullable|numeric',

            'DA'=>'nullable|integer','PA'=>'nullable|integer',
            'TF'=>'nullable|integer','CF'=>'nullable|integer',
            'DR'=>'nullable|integer','UR'=>'nullable|integer','ZA'=>'nullable|integer',
            'as_metric'=>'nullable|integer','seozoom'=>'nullable|string|max:255',
            'semrush_traffic'=>'nullable|integer','ahrefs_keyword'=>'nullable|integer',
            'ahrefs_traffic'=>'nullable|integer','kialvo_evaluation'=>'nullable|numeric',

            'first_contact_date'=>'nullable|date_format:d/m/Y',
            'date_publisher_price'=>'nullable|date_format:d/m/Y',
            'date_kialvo_evaluation'=>'nullable|date_format:d/m/Y',
            'seo_metrics_date'=>'nullable|date_format:d/m/Y',

            'betting'=>'boolean','trading'=>'boolean','permanent_link'=>'boolean',
            'more_than_one_link'=>'boolean','copywriting'=>'boolean',
            'no_sponsored_tag'=>'boolean','social_media_sharing'=>'boolean',
            'post_in_homepage'=>'boolean','extra_notes'=>'nullable|string',

            'category_ids'=>'nullable|array','category_ids.*'=>'integer',
        ]);
    }

    private function normaliseDates(array &$d): void
    {
        foreach (['first_contact_date','date_publisher_price','date_kialvo_evaluation','seo_metrics_date'] as $f) {
            if (!empty($d[$f])) {
                try { $d[$f]=Carbon::createFromFormat('d/m/Y',$d[$f])->format('Y-m-d'); }
                catch (\Exception $e) {}
            }
        }
    }

    private function recalc(NewEntry $e): void
    {
        $da=$e->DA??0; $tf=$e->TF??0; $dr=$e->DR??0; $sr=$e->semrush_traffic??0;
        $auto = ($da*2.4)+($tf*1.45)+($dr*0.5);
        if($sr>=9700) $auto+=($sr/15000)*1.35;
        $e->automatic_evaluation=$auto;
        $e->profit=($e->kialvo_evaluation??0)-($e->publisher_price??0);
        $e->TF_vs_CF=($e->CF??0)?($e->TF/$e->CF):0;
        $e->keyword_vs_traffic=($e->ahrefs_traffic??0)
            ? round(($e->ahrefs_keyword??0)/$e->ahrefs_traffic,2) : 0;
    }

    /** Compute derived metrics on an associative array (form payload). */
    private function recalcArray(array &$d): void
    {
        $da = (float) ($d['DA'] ?? 0);
        $tf = (float) ($d['TF'] ?? 0);
        $dr = (float) ($d['DR'] ?? 0);
        $sr = (float) ($d['semrush_traffic'] ?? 0);

        // Automatic evaluation (same logic you used before)
        $auto = ($da * 2.4) + ($tf * 1.45) + ($dr * 0.5);
        if ($sr >= 9700) {
            $auto += ($sr / 15000) * 1.35;
        }
        $d['automatic_evaluation'] = $auto;

        // Profit (basic form)
        $d['profit'] = (float) ($d['kialvo_evaluation'] ?? 0) - (float) ($d['publisher_price'] ?? 0);

        // TF vs CF
        $cf = (float) ($d['CF'] ?? 0);
        $d['TF_vs_CF'] = $cf ? round(((float) ($d['TF'] ?? 0)) / $cf, 2) : 0;

        // Keyword vs traffic
        $ahrefsTraffic = (float) ($d['ahrefs_traffic'] ?? 0);
        $d['keyword_vs_traffic'] = $ahrefsTraffic
            ? round(((float) ($d['ahrefs_keyword'] ?? 0)) / $ahrefsTraffic, 2)
            : 0;
    }

    /** Derived metrics used by bulk ops; now also ensures automatic_evaluation is set. */
    /** dd/mm/yyyy → yyyy-mm-dd */
    private function euDate(?string $v): ?string
    {
        if (!$v) return null;
        try   { return Carbon::createFromFormat('d/m/Y', $v)->format('Y-m-d'); }
        catch (\Exception $e) { return $v; }
    }
}
