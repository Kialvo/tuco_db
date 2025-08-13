<?php

namespace App\Http\Controllers;

use App\Models\NewEntry;
use App\Models\Website;
use App\Models\Category;
use App\Models\Country;
use App\Models\Language;
use App\Models\Contact;
use Illuminate\Http\Request;
use Yajra\DataTables\DataTables;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use AmrShawky\Currency\Facade\Currency;


class NewEntryController extends Controller
{
    /* =========================================================
     *  LIST + DATATABLE
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
        /* ─────────────────────────────────────────────
         * 1) base query + eager-loads
         * ────────────────────────────────────────────*/
        $q = NewEntry::with(['country', 'language', 'contact', 'categories']);

        // (all your existing filters – unchanged)
        if ($v = $r->domain_name) $q->where('domain_name', 'like', "%$v%");
        if ($v = $r->status)      $q->where('status', $v);
        if ($v = $r->country_id)  $q->where('country_id', $v);
        if ($v = $r->language_id) $q->where('language_id', $v);

        if ($ids = $r->category_ids) {
            $ids = is_array($ids) ? $ids : explode(',', $ids);
            $q->whereHas('categories', fn ($x) => $x->whereIn('categories.id', $ids));
        }

        if ($r->boolean('research_mode')) {
            $q->whereNot(function ($x) {
                $x->whereIn('status', ['negotiation','active','publisher_refused','refused_by_us'])
                    ->orWhere(function ($y) {
                        $y->where('status', 'waiting_for_1st_answer')
                            ->whereDate('first_contact_date', '>', now()->subDays(15));
                    });
            });
        }

        if ($r->boolean('show_deleted')) {
            $q->onlyTrashed();
        }

        /* ─────────────────────────────────────────────
         * 2) DataTables build-up
         * ────────────────────────────────────────────*/
        return DataTables::of($q)
            ->addColumn('country_name',    fn ($r) => optional($r->country)->country_name)
            ->addColumn('language_name',   fn ($r) => optional($r->language)->name)
            ->addColumn('contact_name',    fn ($r) => optional($r->contact)->name)
            ->addColumn('categories_list', fn ($r) => $r->categories->pluck('name')->join(', '))

            /* ------------- Action buttons ------------- */
            ->addColumn('action', function ($row) {

                /* If the entry is trashed → only “Restore” */
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
        </form>
                ';
                }

                /* Otherwise → View · Edit · Delete */
                $viewUrl   = route('new_entries.show',    $row->id);
                $editUrl   = route('new_entries.edit',    $row->id);
                $deleteUrl = route('new_entries.destroy', $row->id);

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
                    onclick="return confirm(\'Are you sure you want to delete this entry?\')"
                    class="inline-flex items-center bg-red-600 text-white px-3 py-1 rounded shadow-sm
                           hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500">
                    <i class="fas fa-trash mr-1"></i> Delete
                </button>
            </form>

        </div>
            ';
            })

            ->rawColumns(['action'])   // allow HTML in the action cell
            ->make(true);
    }


    /* =========================================================
     *  INLINE STATUS UPDATE (AJAX)
     * =======================================================*/
    // 1. updateStatus -------------------------------------------------
    public function updateStatus(Request $r, NewEntry $new_entry)
    {
        $r->validate(['status'=>'required|string|max:255']);

        // normalise to lower-case
        $old = strtolower($new_entry->status);
        $new = strtolower($r->status);

        DB::transaction(function () use ($new_entry, $new, $old) {

            $new_entry->status = $new;
            $new_entry->save();

            // compare with lower-case literal now
            if ($old !== 'active' && $new === 'active') {
                $this->moveToWebsites($new_entry);
            }
        });

        return response()->json(['ok'=>true]);
    }


    /* =========================================================
     *  CREATE / STORE / EDIT / UPDATE / DESTROY
     *  (unchanged except they now call moveToWebsites internally)
     * =======================================================*/
    public function create() { return view('new_entries.create', $this->lookups()); }

    public function store(Request $r)
    {
        /* 1) Validate form */
        $data = $this->validateForm($r);

        /* 2) Date fields to ISO */
        foreach ([
                     'first_contact_date',
                     'date_publisher_price',
                     'date_kialvo_evaluation',
                     'seo_metrics_date',
                 ] as $f) {
            $data[$f] = $this->euDate($data[$f] ?? null);
        }

        /* 3) Automatic maths */
        $this->recalcArray($data);   // see helper below ⬇


        /* 4) DB write */
        DB::transaction(function () use ($data, $r, &$entry) {

            $entry = NewEntry::create($data);

            $entry->categories()->sync($r->category_ids ?? []);

            // if ACTIVE, clone into Websites
            if (strtolower($entry->status) === 'active') {
                $this->moveToWebsites($entry);
            }
        });

        return redirect()->route('new_entries.edit', $entry->id)
                      ->with('status', 'Entry created – you can now complete / review it!');
    }


    public function edit(NewEntry $new_entry)
    {
        $new_entry->load('categories');
        return view('new_entries.edit',
            array_merge(['entry'=>$new_entry], $this->lookups()));
    }

    public function update(Request $r, NewEntry $new_entry)
    {
        $data = $this->validateForm($r);

        /* normalise dates */
        foreach ([
                     'first_contact_date',
                     'date_publisher_price',
                     'date_kialvo_evaluation',
                     'seo_metrics_date',
                 ] as $f) {
            $data[$f] = $this->euDate($data[$f] ?? null);
        }

        /* automatic maths on the *incoming* data */
        $this->recalcArray($data);

        $wasActive = strtolower($new_entry->status) === 'active';

        DB::transaction(function () use ($data, $r, $new_entry, $wasActive) {
            $new_entry->fill($data)->save();
            $new_entry->categories()->sync($r->category_ids ?? []);

            $nowActive = strtolower($new_entry->status) === 'active';
            if (!$wasActive && $nowActive) {
                $this->moveToWebsites($new_entry);
            }
        });

        return redirect()->route('new_entries.index')
                       ->with('status', 'Entry updated!');
    }

    /**
     * Mutate an array with all automatic calculations
     * (used by both store() and update()).
     */
    private function recalcArray(array &$d): void
    {
        $da = $d['DA'] ?? 0;
        $tf = $d['TF'] ?? 0;
        $dr = $d['DR'] ?? 0;
        $sr = $d['semrush_traffic'] ?? 0;

        $auto = ($da * 2.4) + ($tf * 1.45) + ($dr * 0.5);
        if ($sr >= 9700) {
            $auto += ($sr / 15000) * 1.35;
        }
        $d['automatic_evaluation'] = $auto;

        /* profit */
        $d['profit'] = ($d['kialvo_evaluation'] ?? 0) - ($d['publisher_price'] ?? 0);

        /* TF vs CF */
        $cf = $d['CF'] ?? 0;
        $d['TF_vs_CF'] = $cf ? (($d['TF'] ?? 0) / $cf) : 0;

        /* keyword vs traffic */
        $ahrefsTraffic = $d['ahrefs_traffic'] ?? 0;
        $d['keyword_vs_traffic'] = $ahrefsTraffic
            ? round(($d['ahrefs_keyword'] ?? 0) / $ahrefsTraffic, 2)
            : 0;
    }


    /**
     * Display a single New Entry.
     */
    public function show(NewEntry $new_entry)
    {
        // eager-load the look-ups so the Blade can access them without N+1
        $new_entry->load(['country', 'language', 'contact', 'categories']);

        return view('new_entries.show', compact('new_entry'));
    }

    public function destroy(NewEntry $new_entry)
    {
        $new_entry->delete();
        return back()->with('status','Entry deleted (soft).');
    }

    /* =========================================================
     *  INTERNAL HELPERS
     * =======================================================*/
    private function moveToWebsites(NewEntry $e): void
    {
        // Copy only model-fillable attributes
        $data = $e->only((new Website)->getFillable());

        // --- normalize dates coming from NewEntry (dd/mm/yyyy -> Y-m-d)
        foreach (['date_publisher_price','date_kialvo_evaluation','seo_metrics_date'] as $f) {
            if (!empty($data[$f])) {
                try {
                    $data[$f] = \Carbon\Carbon::createFromFormat('d/m/Y', $data[$f])->format('Y-m-d');
                } catch (\Throwable $ignored) { /* leave as-is */ }
            }
        }

        // --- fields affected by the websites trigger
        $triggerFields = [
            'publisher_price','link_insertion_price','no_follow_price',
            'special_topic_price','banner_price','sitewide_link_price',
            'profit','automatic_evaluation',
        ];

        // --- when currency_code is USD, pre-divide to neutralize BEFORE INSERT conversion
        if (!empty($data['currency_code']) && strtoupper($data['currency_code']) === 'USD') {
            // read the same rate the trigger uses
            $rate = (float) DB::table('app_settings')
                ->where('setting_name','usd_eur_rate')
                ->value('setting_value');

            if ($rate > 0) {
                foreach ($triggerFields as $f) {
                    if (isset($data[$f]) && $data[$f] !== '' && $data[$f] !== null) {
                        // keep precision; trigger will multiply back
                        $val = (float) str_replace(',', '.', $data[$f]);
                        $data[$f] = round($val / $rate, 8);
                    }
                }
            }
        }

        // --- ensure original_* are set (store the pre-divided USD values as "original")
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

        // --- insert (trigger runs, multiplying USD values; end result = values you saw in NewEntry)
        $website = Website::create($data);

        // copy categories
        $website->categories()->sync($e->categories->pluck('id'));

        // mark entry as copied
        $e->updateQuietly([
            'copied_to_overview' => 1,
            'date_added'         => \Carbon\Carbon::now(),
        ]);
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
        /* identical rules you already have for Websites/NewEntry */
        return $r->validate([
            'domain_name' => 'required|string|max:255',
            'status'      => 'nullable|string|max:255',
            'linkbuilder'  => 'nullable|string|max:255',
            'country_id'  => 'nullable|integer',  'language_id' =>'nullable|integer',
            'contact_id'  => 'nullable|integer',  'currency_code'=>'nullable|string|max:10',
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
                catch (\Exception $e) { /* leave */ }
            }
        }
    }

    private function recalc(NewEntry $e): void
    {
        $da=$e->DA??0; $tf=$e->TF??0; $dr=$e->DR??0; $sr=$e->semrush_traffic??0;
        $auto = ($da*2.4)+($tf*1.45)+($dr*0.5); if($sr>=9700)$auto+=($sr/15000)*1.35;
        $e->automatic_evaluation=$auto;
        $e->profit=($e->kialvo_evaluation??0)-($e->publisher_price??0);
        $e->TF_vs_CF=($e->CF??0)?($e->TF/$e->CF):0;
        $e->keyword_vs_traffic=($e->ahrefs_traffic??0)
            ? round(($e->ahrefs_keyword??0)/$e->ahrefs_traffic,2) : 0;
    }

    /** Convert `dd/mm/yyyy` → `yyyy-mm-dd` (or return null / unchanged). */
    private function euDate(?string $v): ?string
    {
        if (!$v) return null;
        try   { return \Carbon\Carbon::createFromFormat('d/m/Y', $v)->format('Y-m-d'); }
        catch (\Exception $e) { return $v; }
    }

}
