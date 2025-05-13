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
                // If soft‑deleted show RESTORE
                if ($c->deleted_at) {
                    $restoreUrl = route('copy.restore', $c->id);
                    return '<form action="' . $restoreUrl . '" method="POST" style="display:inline;">'
                        . csrf_field() .
                        '<button onclick="return confirm(\'Restore this record?\')" '
                        . 'class="inline-flex items-center bg-green-600 text-white px-3 py-1 rounded shadow-sm '
                        . 'hover:bg-green-700">'
                        . '<i class="fas fa-undo-alt mr-1"></i> Restore</button></form>';
                }

                // Otherwise EDIT / DELETE
                $deleteUrl = route('copy.destroy', $c->id);

                return '<button type="button" '
                    . 'class="editBtn inline-flex items-center bg-cyan-600 text-white px-3 py-1 rounded shadow-sm '
                    . 'hover:bg-cyan-700 mr-1" '
                    . 'data-copy-id="' . $c->id . '"><i class="fas fa-pen mr-1"></i> Edit</button>'
                    . '<form action="' . $deleteUrl . '" method="POST" style="display:inline-block;">'
                    . csrf_field() . method_field('DELETE') .
                    '<button type="submit" onclick="return confirm(\'Are you sure?\');" '
                    . 'class="inline-flex items-center bg-red-600 text-white px-3 py-1 rounded shadow-sm '
                    . 'hover:bg-red-700">'
                    . '<i class="fas fa-trash mr-1"></i> Delete</button></form>';
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
