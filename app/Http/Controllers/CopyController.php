<?php

namespace App\Http\Controllers;

use App\Models\Copy;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class CopyController extends Controller
{
    /*======================================================================
    |  INDEX – blade fallback when DataTables isn’t used
    ======================================================================*/
    public function index()
    {
        $copies = Copy::all();
        return view('copy.index', compact('copies'));
    }

    /*======================================================================
    |  DATATABLES JSON FEED
    ======================================================================*/
    public function getData(Request $request)
    {
        // id, copy_val, deleted_at only
        $copies = Copy::select(['id', 'copy_val', 'deleted_at']);

        if ($request->boolean('show_deleted')) {
            $copies->onlyTrashed();
        }

        return datatables()->of($copies)
            ->addColumn('excerpt', fn($c) => Str::limit($c->copy_val, 50, '…'))
            ->addColumn('action', function ($c) {
                $iconEdit    = '<svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>';
                $iconTrash   = '<svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>';
                $iconRestore = '<svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M3 10h10a8 8 0 018 8v2M3 10l6 6m-6-6l6-6"/></svg>';

                if ($c->deleted_at) {
                    $restoreUrl = route('copy.restore', $c->id);
                    return '
                        <form action="'.$restoreUrl.'" method="POST" class="inline">
                            '.csrf_field().'
                            <button type="submit" title="Restore"
                                    onclick="return confirm(\'Restore this record?\')"
                                    class="inline-flex items-center justify-center w-7 h-7 rounded-md bg-purple-50 text-purple-700 hover:bg-purple-100 transition">'.$iconRestore.'</button>
                        </form>';
                }

                $deleteUrl = route('copy.destroy', $c->id);
                return '
                    <div class="inline-flex items-center gap-1">
                        <button type="button" title="Edit"
                                data-copy-id="'.$c->id.'"
                                class="editBtn inline-flex items-center justify-center w-7 h-7 rounded-md bg-gray-100 text-gray-700 hover:bg-blue-100 hover:text-blue-700 transition">'.$iconEdit.'</button>
                        <form action="'.$deleteUrl.'" method="POST" class="inline">
                            '.csrf_field().method_field('DELETE').'
                            <button type="submit" title="Delete"
                                    onclick="return confirm(\'Delete this record?\');"
                                    class="inline-flex items-center justify-center w-7 h-7 rounded-md bg-gray-100 text-gray-700 hover:bg-red-100 hover:text-red-700 transition">'.$iconTrash.'</button>
                        </form>
                    </div>';
            })
            ->rawColumns(['action'])
            ->make(true);
    }

    /*======================================================================
    |  CREATE / STORE
    ======================================================================*/
    public function create()
    {
        return view('copy.create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'copy_val' => 'required|max:255',
        ]);

        Copy::create($validated);

        return redirect()->route('copy.index')->with('success', 'Copy created successfully.');
    }

    /*======================================================================
    |  EDIT / UPDATE
    ======================================================================*/
    public function edit(Copy $copy)
    {
        return view('copy.edit', compact('copy'));
    }

    public function update(Request $request, Copy $copy)
    {
        $validated = $request->validate([
            'copy_val' => 'required|max:255',
        ]);

        $copy->update($validated);

        return redirect()->route('copy.index')->with('success', 'Copy updated successfully.');
    }

    /*======================================================================
    | AJAX HELPERS
    ======================================================================*/
    public function editAjax($id)
    {
        $copy = Copy::findOrFail($id);
        return response()->json(['status' => 'success', 'data' => $copy]);
    }

    public function showAjax($id)
    {
        // eager‑load related storages & websites (minimal set)
        $copy = Copy::with([
            'storages:id,copy_id,status',
//            'websites:id,copy_id,domain_name',
        ])->findOrFail($id);

        $data = [
            'id'        => $copy->id,
            'copy_val'  => $copy->copy_val,
            'storages'  => $copy->storages->map(fn($s) => [
                'id'     => $s->id,
                'status' => $s->status,
            ]),
//            'websites'  => $copy->websites->map(fn($w) => [
//                'id'          => $w->id,
//                'domain_name' => $w->domain_name,
//            ]),
        ];

        return response()->json(['status' => 'success', 'data' => $data]);
    }

    /*======================================================================
    | RESTORE (soft‑deleted rows)
    ======================================================================*/
    public function restore($id)
    {
        $copy = Copy::onlyTrashed()->findOrFail($id);
        $copy->restore();
        return redirect()->route('copy.index')->with('status', 'Copy restored successfully!');
    }

    /*======================================================================
    | DESTROY – soft delete
    ======================================================================*/
    public function destroy(Copy $copy)
    {
        $copy->delete();
        return redirect()->route('copy.index')->with('success', 'Copy soft‑deleted successfully.');
    }
}
