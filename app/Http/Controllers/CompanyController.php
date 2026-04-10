<?php

namespace App\Http\Controllers;

use App\Models\Company;
use Illuminate\Http\Request;

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
    |  DATATABLES FEED
    ======================================================================*/
    public function getData(Request $request)
    {
        $companies = Company::withCount('clients');

        return datatables()->of($companies)
            ->addColumn('action', function (Company $c) {
                $deleteUrl = route('companies.destroy', $c->id);
                return '<button type="button"
                            class="editBtn inline-flex items-center bg-cyan-600 text-white px-3 py-1 rounded shadow-sm
                                   hover:bg-cyan-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-cyan-500 mr-1"
                            data-company-id="' . $c->id . '"
                            data-company-name="' . e($c->name) . '">
                            <i class="fas fa-pen mr-1"></i> Edit</button>'
                    . '<form action="' . $deleteUrl . '" method="POST" style="display:inline-block;">'
                    . csrf_field() . method_field('DELETE')
                    . '<button type="submit" onclick="return confirm(\'Delete this company? Clients will have their company cleared.\');"
                               class="inline-flex items-center bg-red-600 text-white px-3 py-1 rounded shadow-sm
                                      hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500">
                        <i class="fas fa-trash mr-1"></i> Delete</button></form>';
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
