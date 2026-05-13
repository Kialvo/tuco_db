<?php
namespace App\Http\Controllers;

use App\Models\HistoricalEntry;
use App\Models\Country;
use App\Models\Language;
use Illuminate\Http\Request;
use Yajra\DataTables\DataTables;

class HistoricalEntryController extends Controller
{
    /* ───── index() ───── */
    public function index()
    {
        return view('historical_view.index', [
            'countries' => Country::all(),
            'languages' => Language::all(),
        ]);
    }

    /* ───── DataTable feed ───── */
    public function getData(Request $r)
    {
        $q = HistoricalEntry::query()
            ->select('v_new_entries_filtered.*')
            ->selectSub(function ($sub) {
                $sub->from('new_entries')
                    ->select('price')
                    ->whereColumn('new_entries.id', 'v_new_entries_filtered.id')
                    ->limit(1);
            }, 'manual_price')
            ->selectSub(function ($sub) {
                $sub->from('new_entries')
                    ->select('sensitive_topic_price')
                    ->whereColumn('new_entries.id', 'v_new_entries_filtered.id')
                    ->limit(1);
            }, 'manual_sensitive_topic_price')
            ->with(['country','language','contact','categories']);

        /* same filters you already use in NewEntryController ---------------- */
        if ($v = $r->domain_name) $q->where('domain_name','like',"%$v%");
        if ($v = $r->status)      $q->where('status',$v);
        if ($v = $r->country_ids)  $q->where('country_id', $v);
        if ($v = $r->language_id) $q->where('language_id', $v);
        if ($f = $r->first_contact_from) $q->whereDate('first_contact_date','>=',$f);
        if ($t = $r->first_contact_to)   $q->whereDate('first_contact_date','<=',$t);

        /* no inline-editing in the historical view */
        return DataTables::of($q)
            ->editColumn('price', fn($r) => $r->manual_price)
            ->editColumn('sensitive_topic_price', fn($r) => $r->manual_sensitive_topic_price)
            ->addColumn('country_name',    fn($r)=>optional($r->country )->country_name)
            ->addColumn('country_iso',     fn($r)=>\App\Support\CountryCode::iso(optional($r->country)->country_name))
            ->addColumn('language_name',   fn($r)=>optional($r->language)->name)
            ->addColumn('contact_name',    fn($r)=>optional($r->contact )->name)
            ->addColumn('categories_list', fn($r)=>$r->categories->pluck('name')->join(', '))
            ->addColumn('action', function ($row) {
                $iconEdit = '<svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>';
                return '
                    <div class="inline-flex items-center gap-1">
                        <a href="'.route('new_entries.edit', $row->id).'" title="View / Edit"
                           class="inline-flex items-center justify-center w-7 h-7 rounded-md bg-gray-100 text-gray-700 hover:bg-blue-100 hover:text-blue-700 transition">'.$iconEdit.'</a>
                    </div>';
            })
            ->rawColumns(['action'])
            ->make(true);
    }
}
