<?php

namespace App\Http\Controllers;

use App\Models\Contact;
use Illuminate\Http\Request;

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
        $contacts = Contact::select(['id', 'name', 'email', 'phone', 'facebook', 'instagram']);

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
                    ? '<a href="' . $contact->facebook . '" target="_blank">' . $contact->facebook . '</a>'
                    : '';
            })
            // Add a new column for Instagram (as a clickable link if available).
            ->addColumn('contact_instagram', function ($contact) {
                return $contact->instagram
                    ? '<a href="' . $contact->instagram . '" target="_blank">' . $contact->instagram . '</a>'
                    : '';
            })
            // Add the Action column (Edit/Delete buttons).
            ->addColumn('action', function ($contact) {
                // If this row is soft-deleted, we only show a “Restore” button
                if ($contact->trashed()) {
                    $restoreUrl = route('contacts.restore', $contact->id);
                    return '
                    <form action="'.$restoreUrl.'" method="POST" style="display:inline;">
                        '.csrf_field().'
                        <button onclick="return confirm(\'Are you sure you want to restore this contact?\')" class="text-green-600 underline">
                            Restore
                        </button>
                    </form>
                ';
                }

                return '
                <a href="' . route('contacts.edit', $contact->id) . '" class="btn btn-sm btn-warning">Edit</a>
                <form action="' . route('contacts.destroy', $contact->id) . '" method="POST" style="display:inline-block;">
                    ' . csrf_field() . method_field('DELETE') . '
                    <button type="submit" class="btn btn-sm btn-danger" onclick="return confirm(\'Are you sure you want to delete this contact?\');">Delete</button>
                </form>
            ';
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

    public function restore($id)
    {
        // Retrieve the trashed record
        $contact = Contact::onlyTrashed()->findOrFail($id);

        // Restore it
        $contact->restore();

        return redirect()->route('contacts.index')->with('status', 'Contact restored successfully!');
    }

    /**
     * Remove the specified contact from storage.
     */
    public function destroy(Contact $contact)
    {
        $contact->delete();

        return redirect()->route('contacts.index')->with('success', 'Contact soft-deleted successfully.');
    }
}
