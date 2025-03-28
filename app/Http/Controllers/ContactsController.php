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
                    ? '<a href="' . $contact->facebook . '" target="_blank">' . $contact->facebook . '</a>'
                    : '';
            })
            // Add a new column for Instagram (as a clickable link if available).
            ->addColumn('contact_instagram', function ($contact) {
                return $contact->instagram
                    ? '<a href="' . $contact->instagram . '" target="_blank">' . $contact->instagram . '</a>'
                    : '';
            })
            // Add the Action column (Edit/Delete/Restore buttons).
            ->addColumn('action', function ($contact) {
                // If soft-deleted, show "Restore" button
                if ($contact->deleted_at !== null) {
                    $restoreUrl = route('contacts.restore', $contact->id);
                    return '
            <form action="' . $restoreUrl . '" method="POST" style="display:inline;">
                ' . csrf_field() . '
                <button onclick="return confirm(\'Are you sure you want to restore this contact?\')"
                        class="inline-flex items-center bg-green-600 text-white px-3 py-1 rounded shadow-sm hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500">
                    <i class="fas fa-undo-alt mr-1"></i> Restore
                </button>
            </form>
        ';
                }
                // Otherwise, show "Edit" + "Delete" buttons
                $deleteUrl = route('contacts.destroy', $contact->id);
                return '
        <!-- EDIT button triggers a modal via JavaScript -->
        <button type="button"
                class="editBtn inline-flex items-center bg-cyan-600 text-white px-3 py-1 rounded shadow-sm
                       hover:bg-cyan-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-cyan-500 mr-1"
                data-contact-id="' . $contact->id . '">
            <i class="fas fa-pen mr-1"></i> Edit
        </button>

        <!-- DELETE -->
        <form action="' . $deleteUrl . '" method="POST" style="display:inline-block;">
            ' . csrf_field() . method_field('DELETE') . '
            <button type="submit" onclick="return confirm(\'Are you sure?\');"
                    class="inline-flex items-center bg-red-600 text-white px-3 py-1 rounded shadow-sm
                           hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500">
                <i class="fas fa-trash mr-1"></i> Delete
            </button>
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
