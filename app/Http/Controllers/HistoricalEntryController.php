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
        $q = HistoricalEntry::with(['country','language','contact','categories']);

        /* same filters you already use in NewEntryController ---------------- */
        if ($v = $r->domain_name) $q->where('domain_name','like',"%$v%");
        if ($v = $r->status)      $q->where('status',$v);
        if ($ids = $r->country_ids) {
            $q->whereIn('country_id', is_array($ids) ? $ids : explode(',',$ids));
        }
        if ($f = $r->first_contact_from) $q->whereDate('first_contact_date','>=',$f);
        if ($t = $r->first_contact_to)   $q->whereDate('first_contact_date','<=',$t);

        /* no inline-editing in the historical view */
        return DataTables::of($q)
            ->addColumn('country_name',    fn($r)=>optional($r->country )->country_name)
            ->addColumn('language_name',   fn($r)=>optional($r->language)->name)
            ->addColumn('contact_name',    fn($r)=>optional($r->contact )->name)
            ->addColumn('categories_list', fn($r)=>$r->categories->pluck('name')->join(', '))
            ->addColumn('action', fn($row)=>'
                    <a href="'.route('new_entries.edit',$row->id).'"
                       class="inline-flex items-center bg-cyan-600 text-white px-3 py-1 rounded shadow-sm
                              hover:bg-cyan-700 focus:outline-none focus:ring-2 focus:ring-offset-2
                              focus:ring-cyan-500">
                        <i class=\"fas fa-eye mr-1\"></i> View
                    </a>')
            ->rawColumns(['action'])
            ->make(true);
    }
}
