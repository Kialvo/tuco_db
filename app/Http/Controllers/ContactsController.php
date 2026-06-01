<?php

namespace App\Http\Controllers;

use App\Models\Contact;
use Illuminate\Http\Request;
use function PHPUnit\Framework\isNull;

class ContactsController extends Controller
{
    /**
     * Display a listing of the contacts.
     */
    public function index()
    {
        // If you are not using DataTables, simply load all contacts:
        $contacts = Contact::all();
        return view('contacts.index', compact('contacts'));
    }

    /**
     * Return data for DataTables (optional).
     */
    public function getData(Request $request)
    {
        // Retrieve the contacts from the database.
        $contacts = Contact::select(['id', 'name', 'email', 'phone', 'facebook', 'instagram', 'deleted_at']);

        if ($request->boolean('show_deleted')) {
            $contacts->onlyTrashed();
        }

        return datatables()->of($contacts)
            // Add a new column for Contact Name.
            ->addColumn('contact_name', function ($contact) {
                return $contact->name;
            })
            // Add a new column for Email.
            ->addColumn('contact_email', function ($contact) {
                return $contact->email;
            })
            // Add a new column for Phone.
            ->addColumn('contact_phone', function ($contact) {
                return $contact->phone;
            })
            // Add a new column for Facebook (as a clickable link if available).
            ->addColumn('contact_facebook', function ($contact) {
                return $contact->facebook
                    ? '<a href="'.$contact->facebook.'" target="_blank" class="text-green-600 hover:text-green-700">'.$contact->facebook.'</a>'
                    : '<span class="text-gray-300">—</span>';
            })
            // Add a new column for Instagram (as a clickable link if available).
            ->addColumn('contact_instagram', function ($contact) {
                return $contact->instagram
                    ? '<a href="'.$contact->instagram.'" target="_blank" class="text-green-600 hover:text-green-700">'.$contact->instagram.'</a>'
                    : '<span class="text-gray-300">—</span>';
            })
            // Add the Action column (Edit/Delete/Restore buttons).
            ->addColumn('action', function ($contact) {
                $iconEdit    = '<svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>';
                $iconTrash   = '<svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>';
                $iconRestore = '<svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M3 10h10a8 8 0 018 8v2M3 10l6 6m-6-6l6-6"/></svg>';

                if ($contact->deleted_at !== null) {
                    $restoreUrl = route('contacts.restore', $contact->id);
                    return '
                        <form action="'.$restoreUrl.'" method="POST" class="inline">
                            '.csrf_field().'
                            <button type="submit" title="Restore"
                                    onclick="return confirm(\'Restore this contact?\')"
                                    class="inline-flex items-center justify-center w-7 h-7 rounded-md bg-purple-50 text-purple-700 hover:bg-purple-100 transition">'.$iconRestore.'</button>
                        </form>';
                }

                $deleteUrl = route('contacts.destroy', $contact->id);
                return '
                    <div class="inline-flex items-center gap-1">
                        <button type="button" title="Edit"
                                data-contact-id="'.$contact->id.'"
                                class="editBtn inline-flex items-center justify-center w-7 h-7 rounded-md bg-gray-100 text-gray-700 hover:bg-blue-100 hover:text-blue-700 transition">'.$iconEdit.'</button>
                        <form action="'.$deleteUrl.'" method="POST" class="inline">
                            '.csrf_field().method_field('DELETE').'
                            <button type="submit" title="Delete"
                                    onclick="return confirm(\'Delete this contact?\');"
                                    class="inline-flex items-center justify-center w-7 h-7 rounded-md bg-gray-100 text-gray-700 hover:bg-red-100 hover:text-red-700 transition">'.$iconTrash.'</button>
                        </form>
                    </div>';
            })

            // Let DataTables know that these columns contain HTML.
            ->rawColumns(['contact_facebook', 'contact_instagram', 'action'])
            ->make(true);
    }

    /**
     * Show the form for creating a new contact.
     */
    public function create()
    {
        return view('contacts.create');
    }

    /**
     * Store a newly created contact in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name'      => 'required|max:255',
            'email'     => 'required|email|max:255',
            'phone'     => 'nullable|max:20',
            'facebook'  => 'nullable|url|max:255',
            'instagram' => 'nullable|url|max:255',
        ]);

        Contact::create($validated);

        return redirect()->route('contacts.index')->with('success', 'Contact created successfully.');
    }

    /**
     * Show the form for editing the specified contact.
     */
    public function edit(Contact $contact)
    {
        return view('contacts.edit', compact('contact'));
    }

    /**
     * Update the specified contact in storage.
     */
    public function update(Request $request, Contact $contact)
    {
        $validated = $request->validate([
            'name'      => 'required|max:255',
            'email'     => 'required|email|max:255',
            'phone'     => 'nullable|max:20',
            'facebook'  => 'nullable|url|max:255',
            'instagram' => 'nullable|url|max:255',
        ]);

        $contact->update($validated);

        return redirect()->route('contacts.index')->with('success', 'Contact updated successfully.');
    }


    public function editAjax($id)
    {
        $contact = Contact::findOrFail($id);
        return response()->json([
            'status' => 'success',
            'data'   => $contact
        ]);
    }

    /**
     * Restore a soft-deleted contact.
     */
    public function restore($id)
    {
        // Retrieve the trashed record
        $contact = Contact::onlyTrashed()->findOrFail($id);

        // Restore it
        $contact->restore();

        return redirect()->route('contacts.index')->with('status', 'Contact restored successfully!');
    }

    /**
     * Show an individual contact as AJAX response.
     */
    public function showAjax($id)
    {
        // Eager-load "websites" (assuming your Contact model has a hasMany(Website::class, 'contact_id'))
        $contact = Contact::with('websites')->findOrFail($id);

        // Prepare array for JSON response
        $data = [
            'id'        => $contact->id,
            'name'      => $contact->name,
            'email'     => $contact->email,
            'phone'     => $contact->phone,
            'facebook'  => $contact->facebook,
            'instagram' => $contact->instagram,
            // Return an array of websites (id + domain_name)
            'websites'  => $contact->websites->map(function($w) {
                return [
                    'id'          => $w->id,
                    'domain_name' => $w->domain_name,
                ];
            }),
        ];

        // Return JSON
        return response()->json([
            'status' => 'success',
            'data'   => $data
        ]);
    }

    /**
     * Soft-delete the specified contact.
     */
    public function destroy(Contact $contact)
    {
        $contact->delete();

        return redirect()->route('contacts.index')->with('success', 'Contact soft-deleted successfully.');
    }
}
