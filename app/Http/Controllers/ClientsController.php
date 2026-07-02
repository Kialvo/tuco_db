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
        $clients = Client::select(['id', 'first_name', 'last_name', 'email', 'company_id', 'deleted_at'])
            ->with('company:id,name');

        if ($request->boolean('show_deleted')) {
            $clients->onlyTrashed();
        }

        return datatables()->of($clients)
            ->addColumn('client_name', function ($c) {
                return $c->first_name . ' ' . $c->last_name;
            })
            ->addColumn('client_email', fn($c) => $c->email)
            ->addColumn('client_company', fn($c) => optional($c->company)->name)
            ->addColumn('company', fn($c) => optional($c->company)->name)
            ->addColumn('action', function ($c) {
                $iconEdit    = '<svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>';
                $iconTrash   = '<svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>';
                $iconRestore = '<svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M3 10h10a8 8 0 018 8v2M3 10l6 6m-6-6l6-6"/></svg>';

                if ($c->deleted_at) {
                    $restoreUrl = route('clients.restore', $c->id);
                    return '
                        <form action="'.$restoreUrl.'" method="POST" class="inline">
                            '.csrf_field().'
                            <button type="submit" title="Restore"
                                    onclick="return confirm(\'Restore this client?\')"
                                    class="inline-flex items-center justify-center w-7 h-7 rounded-md bg-purple-50 text-purple-700 hover:bg-purple-100 transition">'.$iconRestore.'</button>
                        </form>';
                }

                $deleteUrl = route('clients.destroy', $c->id);
                return '
                    <div class="inline-flex items-center gap-1">
                        <button type="button" title="Edit"
                                data-client-id="'.$c->id.'"
                                class="editBtn inline-flex items-center justify-center w-7 h-7 rounded-md bg-gray-100 text-gray-700 hover:bg-blue-100 hover:text-blue-700 transition">'.$iconEdit.'</button>
                        <form action="'.$deleteUrl.'" method="POST" class="inline">
                            '.csrf_field().method_field('DELETE').'
                            <button type="submit" title="Delete"
                                    onclick="return confirm(\'Delete this client?\');"
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
        return view('clients.create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'first_name' => 'required|max:255',
            'last_name'  => 'required|max:255',
            'email'      => 'required|email|max:255',
            'company_id' => 'nullable|exists:companies,id',
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
            'company_id' => 'nullable|exists:companies,id',
        ]);

        $client->update($validated);

        return redirect()->route('clients.index')->with('success', 'Client updated successfully.');
    }

    /*======================================================================
    |  AJAX HELPERS (edit form prefill, detailed info)
    ======================================================================*/
    public function editAjax($id)
    {
        $client = Client::with('company:id,name')->findOrFail($id);
        return response()->json(['status' => 'success', 'data' => [
            'id'           => $client->id,
            'first_name'   => $client->first_name,
            'last_name'    => $client->last_name,
            'email'        => $client->email,
            'company_id'   => $client->company_id,
            'company_name' => optional($client->company)->name,
        ]]);
    }

    public function showAjax($id)
    {
        $client = Client::with(['company:id,name', 'storages:id,client_id,status'])
            ->findOrFail($id);

        $data = [
            'id'           => $client->id,
            'first_name'   => $client->first_name,
            'last_name'    => $client->last_name,
            'email'        => $client->email,
            'company'      => optional($client->company)->name,
            'company_id'   => $client->company_id,
            'company_name' => optional($client->company)->name,
            'storages'     => $client->storages->map(fn ($s) => [
                'id'          => $s->id,
                'domain_name' => $s->site?->domain_name ?? '',
                'status'      => $s->status ?? '',
            ]),
        ];

        return response()->json(['status' => 'success', 'data' => $data]);
    }

    /*======================================================================
    |  SHOW – CRM detail page (admin-only route). Contact profile + its
    |  Link Building campaigns.
    ======================================================================*/
    public function show(Client $client)
    {
        $client->load('company:id,name');

        $campaigns = $client->campaigns()
            ->with(['company:id,name', 'responsibleUser:id,name'])
            ->latest()
            ->get();

        return view('clients.show', compact('client', 'campaigns'));
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
