<?php

namespace App\Http\Controllers;

use App\Models\Company;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class CompanyController extends Controller
{
    /*======================================================================
    |  INDEX
    ======================================================================*/
    public function index()
    {
        return view('companies.index');
    }

    /*======================================================================
    |  SHOW – CRM detail page (admin-only route). Read-only reference of the
    |  shared company record + its Link Building campaigns and contacts.
    ======================================================================*/
    public function show(Company $company)
    {
        $company->load('clients');

        $campaigns = $company->campaigns()
            ->with(['contact:id,first_name,last_name', 'responsibleUser:id,name'])
            ->latest()
            ->get();

        $countryName = $company->country_id
            ? DB::table('countries')->where('id', $company->country_id)->value('country_name')
            : null;

        return view('companies.show', compact('company', 'campaigns', 'countryName'));
    }

    /*======================================================================
    |  DATATABLES FEED
    ======================================================================*/
    public function getData(Request $request)
    {
        $companies = Company::withCount('clients');

        return datatables()->of($companies)
            ->addColumn('action', function (Company $c) {
                $iconEdit  = '<svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>';
                $iconTrash = '<svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>';
                $deleteUrl = route('companies.destroy', $c->id);
                return '
                    <div class="inline-flex items-center gap-1">
                        <button type="button" title="Edit"
                                data-company-id="'.$c->id.'"
                                data-company-name="'.e($c->name).'"
                                class="editBtn inline-flex items-center justify-center w-7 h-7 rounded-md bg-gray-100 text-gray-700 hover:bg-blue-100 hover:text-blue-700 transition">'.$iconEdit.'</button>
                        <form action="'.$deleteUrl.'" method="POST" class="inline">
                            '.csrf_field().method_field('DELETE').'
                            <button type="submit" title="Delete"
                                    onclick="return confirm(\'Delete this company? Clients will have their company cleared.\');"
                                    class="inline-flex items-center justify-center w-7 h-7 rounded-md bg-gray-100 text-gray-700 hover:bg-red-100 hover:text-red-700 transition">'.$iconTrash.'</button>
                        </form>
                    </div>';
            })
            ->rawColumns(['action'])
            ->make(true);
    }

    /*======================================================================
    |  STORE
    ======================================================================*/
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|max:255|unique:companies,name',
        ]);

        $company = Company::create($validated);

        if ($request->expectsJson()) {
            return response()->json(['status' => 'success', 'company' => $company]);
        }

        return redirect()->route('companies.index')->with('success', 'Company created successfully.');
    }

    /*======================================================================
    |  UPDATE
    ======================================================================*/
    public function update(Request $request, Company $company)
    {
        $validated = $request->validate([
            'name' => 'required|max:255|unique:companies,name,' . $company->id,
        ]);

        $company->update($validated);

        if ($request->expectsJson()) {
            return response()->json(['status' => 'success', 'company' => $company]);
        }

        return redirect()->route('companies.index')->with('success', 'Company updated successfully.');
    }

    /*======================================================================
    |  DESTROY
    ======================================================================*/
    public function destroy(Company $company)
    {
        // clients FK is nullOnDelete — handled by DB
        $company->delete();
        return redirect()->route('companies.index')->with('success', 'Company deleted successfully.');
    }

    /*======================================================================
    |  SEARCH – Select2 AJAX autocomplete
    ======================================================================*/
    public function search(Request $request)
    {
        $term = $request->get('q', '');
        $companies = Company::where('name', 'like', '%' . $term . '%')
            ->orderBy('name')
            ->limit(30)
            ->get(['id', 'name']);

        // Select2 expects { results: [{id, text}] }
        return response()->json([
            'results' => $companies->map(fn($c) => ['id' => $c->id, 'text' => $c->name]),
        ]);
    }
}
