<?php

namespace App\Http\Controllers;

use App\Models\Client;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class ClientsController extends Controller
{
    /*======================================================================
    |  INDEX – list all clients (non‑DataTables fallback)
    ======================================================================*/
    public function index()
    {
        $clients = Client::all();
        return view('clients.index', compact('clients'));
    }

    /*======================================================================
    |  DATATABLES FEED – JSON for ajax tables
    ======================================================================*/
    public function getData(Request $request)
    {
        $clients = Client::select(['id', 'first_name', 'last_name', 'email', 'company', 'deleted_at']);

        if ($request->boolean('show_deleted')) {
            $clients->onlyTrashed();
        }

        return datatables()->of($clients)
            ->addColumn('client_name', function ($c) {
                return $c->first_name . ' ' . $c->last_name;
            })
            ->addColumn('client_email', fn($c) => $c->email)
            ->addColumn('client_company', fn($c) => $c->company)
            ->addColumn('action', function ($c) {
                // If soft‑deleted –> RESTORE
                if ($c->deleted_at) {
                    $restoreUrl = route('clients.restore', $c->id);
                    return '<form action="' . $restoreUrl . '" method="POST" style="display:inline;">'
                        . csrf_field() .
                        '<button onclick="return confirm(\'Restore this client?\')" '
                        . 'class="inline-flex items-center bg-green-600 text-white px-3 py-1 rounded shadow-sm '
                        . 'hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500">'
                        . '<i class="fas fa-undo-alt mr-1"></i> Restore</button></form>';
                }

                // Otherwise EDIT / DELETE
                $deleteUrl = route('clients.destroy', $c->id);
                return '<button type="button" '
                    . 'class="editBtn inline-flex items-center bg-cyan-600 text-white px-3 py-1 rounded shadow-sm '
                    . 'hover:bg-cyan-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-cyan-500 mr-1" '
                    . 'data-client-id="' . $c->id . '"><i class="fas fa-pen mr-1"></i> Edit</button>'
                    . '<form action="' . $deleteUrl . '" method="POST" style="display:inline-block;">'
                    . csrf_field() . method_field('DELETE') .
                    '<button type="submit" onclick="return confirm(\'Are you sure?\');" '
                    . 'class="inline-flex items-center bg-red-600 text-white px-3 py-1 rounded shadow-sm '
                    . 'hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500">'
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
        return view('clients.create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'first_name' => 'required|max:255',
            'last_name'  => 'required|max:255',
            'email'      => 'required|email|max:255',
            'company'    => 'nullable|max:255',
        ]);

        Client::create($validated);

        return redirect()->route('clients.index')->with('success', 'Client created successfully.');
    }

    /*======================================================================
    |  EDIT / UPDATE
    ======================================================================*/
    public function edit(Client $client)
    {
        return view('clients.edit', compact('client'));
    }

    public function update(Request $request, Client $client)
    {
        $validated = $request->validate([
            'first_name' => 'required|max:255',
            'last_name'  => 'required|max:255',
            'email'      => 'required|email|max:255',
            'company'    => 'nullable|max:255',
        ]);

        $client->update($validated);

        return redirect()->route('clients.index')->with('success', 'Client updated successfully.');
    }

    /*======================================================================
    |  AJAX HELPERS (edit form prefill, detailed info)
    ======================================================================*/
    public function editAjax($id)
    {
        $client = Client::findOrFail($id);
        return response()->json(['status' => 'success', 'data' => $client]);
    }

    public function showAjax($id)
    {
        // eager‑load related storages & websites (ids + basic fields)
        $client = Client::with(['storages:id,client_id,status' ])
//            'websites:id,client_id,domain_name',
            ->findOrFail($id);

        $data = [
            'id'        => $client->id,
            'first_name'=> $client->first_name,
            'last_name' => $client->last_name,
            'email'     => $client->email,
            'company'   => $client->company,
            'storages'  => $client->storages->map(fn ($s) => [
                'id'          => $s->id,
                'domain_name' => $s->site?->domain_name ?? '',
                'status'      => $s->status ?? '',
            ]),
//            'websites'  => $client->websites->map(fn($w) => [
//                'id'          => $w->id,
//                'domain_name' => $w->domain_name,
//            ]),
        ];

        return response()->json(['status' => 'success', 'data' => $data]);
    }

    /*======================================================================
    |  RESTORE (soft‑deleted rows)
    ======================================================================*/
    public function restore($id)
    {
        $client = Client::onlyTrashed()->findOrFail($id);
        $client->restore();
        return redirect()->route('clients.index')->with('status', 'Client restored successfully!');
    }

    /*======================================================================
    |  DESTROY – soft delete
    ======================================================================*/
    public function destroy(Client $client)
    {
        $client->delete();
        return redirect()->route('clients.index')->with('success', 'Client soft‑deleted successfully.');
    }
}
