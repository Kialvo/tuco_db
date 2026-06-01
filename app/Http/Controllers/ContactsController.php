<?php

namespace App\Http\Controllers;

use App\Models\Client;
use App\Models\Language;
use App\Models\Country;
use Illuminate\Http\Request;

class ContactsController extends Controller
{
    public function index()
    {
        return view('contacts.index');
    }

    public function getData(Request $request)
    {
        $q = Client::with(['company', 'originCountry']);

        if ($request->boolean('show_deleted')) {
            $q->onlyTrashed();
        }

        return datatables()->of($q)
            ->addColumn('full_name', fn($c) => trim($c->first_name . ' ' . $c->last_name))
            ->addColumn('company_name', fn($c) => $c->company?->name ?? '')
            ->addColumn('primary_channel_html', function ($c) {
                $type  = $c->channel_1;
                $value = match($type) {
                    'email'    => $c->email,
                    'phone'    => $c->phone,
                    'whatsapp' => $c->whatsapp,
                    'telegram' => $c->telegram,
                    'linkedin' => $c->linkedin,
                    'facebook' => $c->facebook,
                    'discord'  => $c->discord,
                    default    => null,
                };
                if (!$type) return '<span class="text-gray-300">—</span>';
                $colors = [
                    'email'=>'bg-gray-500','phone'=>'bg-gray-500','whatsapp'=>'bg-green-500',
                    'telegram'=>'bg-sky-500','linkedin'=>'bg-blue-600',
                    'facebook'=>'bg-blue-700','discord'=>'bg-indigo-500',
                ];
                $bg = $colors[$type] ?? 'bg-gray-400';
                $label = ucfirst($type);
                return '<span class="inline-flex items-center gap-1.5 text-xs">'
                    .'<span class="inline-flex items-center px-2 py-0.5 rounded-full text-white text-[10px] font-semibold '.$bg.'">'.$label.'</span>'
                    .'<span class="text-gray-600">'.e($value ?? '—').'</span>'
                    .'</span>';
            })
            ->addColumn('origin_country', fn($c) => $c->originCountry?->country_name ?? '')
            ->addColumn('action', function ($c) {
                $editUrl   = route('contacts.edit', $c->id);
                $deleteUrl = route('contacts.destroy', $c->id);
                $iconEdit  = '<svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>';
                $iconTrash = '<svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>';
                $iconRestore = '<svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M3 10h10a8 8 0 018 8v2M3 10l6 6m-6-6l6-6"/></svg>';

                if ($c->deleted_at) {
                    return '<form action="'.route('contacts.restore', $c->id).'" method="POST" class="inline">'.csrf_field().'
                        <button type="submit" class="inline-flex items-center justify-center w-7 h-7 rounded-md bg-purple-50 text-purple-700 hover:bg-purple-100">'.$iconRestore.'</button></form>';
                }
                return '<div class="inline-flex items-center gap-1">
                    <a href="'.$editUrl.'" class="inline-flex items-center justify-center w-7 h-7 rounded-md bg-gray-100 text-gray-700 hover:bg-blue-100 hover:text-blue-700">'.$iconEdit.'</a>
                    <form action="'.$deleteUrl.'" method="POST" class="inline">'.csrf_field().method_field('DELETE').'
                        <button type="submit" onclick="return confirm(\'Delete this contact?\')" class="inline-flex items-center justify-center w-7 h-7 rounded-md bg-gray-100 text-gray-700 hover:bg-red-100 hover:text-red-700">'.$iconTrash.'</button></form></div>';
            })
            ->rawColumns(['primary_channel_html', 'action'])
            ->make(true);
    }

    public function create()
    {
        $languages = Language::orderBy('name')->get();
        $countries = Country::orderBy('country_name')->get();
        return view('contacts.create', compact('languages', 'countries'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'first_name'          => 'required|max:255',
            'last_name'           => 'nullable|max:255',
            'email'               => 'nullable|email|max:255',
            'phone'               => 'nullable|max:50',
            'company_id'          => 'nullable|exists:companies,id',
            'first_contact_date'  => 'nullable|date',
            'job_title'           => 'nullable|max:255',
            'primary_language_id' => 'nullable|exists:languages,id',
            'channel_1'           => 'nullable|in:email,phone,whatsapp,telegram,linkedin,facebook,discord',
            'channel_2'           => 'nullable|in:email,phone,whatsapp,telegram,linkedin,facebook,discord',
            'channel_3'           => 'nullable|in:email,phone,whatsapp,telegram,linkedin,facebook,discord',
            'whatsapp'            => 'nullable|max:50',
            'telegram'            => 'nullable|max:100',
            'facebook'            => 'nullable|url|max:255',
            'discord'             => 'nullable|max:100',
            'linkedin'            => 'nullable|url|max:255',
            'location_address'    => 'nullable|max:255',
            'location_lat'        => 'nullable|numeric',
            'location_lng'        => 'nullable|numeric',
            'location_country_id' => 'nullable|exists:countries,id',
            'birthday'            => 'nullable|date',
            'country_of_origin_id'=> 'nullable|exists:countries,id',
            'religion'            => 'nullable|in:Christianity,Islam,Judaism,Buddhism,Hinduism,Other',
        ]);

        Client::create($validated);

        return redirect()->route('contacts.index')->with('status', 'Contact created successfully.');
    }

    public function edit(Client $contact)
    {
        $languages = Language::orderBy('name')->get();
        $countries = Country::orderBy('country_name')->get();
        return view('contacts.edit', compact('contact', 'languages', 'countries'));
    }

    public function update(Request $request, Client $contact)
    {
        $validated = $request->validate([
            'first_name'          => 'required|max:255',
            'last_name'           => 'nullable|max:255',
            'email'               => 'nullable|email|max:255',
            'phone'               => 'nullable|max:50',
            'company_id'          => 'nullable|exists:companies,id',
            'first_contact_date'  => 'nullable|date',
            'job_title'           => 'nullable|max:255',
            'primary_language_id' => 'nullable|exists:languages,id',
            'channel_1'           => 'nullable|in:email,phone,whatsapp,telegram,linkedin,facebook,discord',
            'channel_2'           => 'nullable|in:email,phone,whatsapp,telegram,linkedin,facebook,discord',
            'channel_3'           => 'nullable|in:email,phone,whatsapp,telegram,linkedin,facebook,discord',
            'whatsapp'            => 'nullable|max:50',
            'telegram'            => 'nullable|max:100',
            'facebook'            => 'nullable|url|max:255',
            'discord'             => 'nullable|max:100',
            'linkedin'            => 'nullable|url|max:255',
            'location_address'    => 'nullable|max:255',
            'location_lat'        => 'nullable|numeric',
            'location_lng'        => 'nullable|numeric',
            'location_country_id' => 'nullable|exists:countries,id',
            'birthday'            => 'nullable|date',
            'country_of_origin_id'=> 'nullable|exists:countries,id',
            'religion'            => 'nullable|in:Christianity,Islam,Judaism,Buddhism,Hinduism,Other',
        ]);

        $contact->update($validated);

        return redirect()->route('contacts.index')->with('status', 'Contact updated successfully.');
    }

    public function editAjax($id)
    {
        $contact = Client::with(['company'])->findOrFail($id);
        return response()->json(['status' => 'success', 'data' => $contact]);
    }

    public function restore($id)
    {
        Client::onlyTrashed()->findOrFail($id)->restore();
        return redirect()->route('contacts.index')->with('status', 'Contact restored successfully.');
    }

    public function destroy(Client $contact)
    {
        $contact->delete();
        return redirect()->route('contacts.index')->with('status', 'Contact deleted successfully.');
    }
}
