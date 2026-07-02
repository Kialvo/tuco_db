<?php

namespace App\Http\Controllers;

use App\Models\Campaign;
use App\Models\Publication;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class PublicationController extends Controller
{
    /*======================================================================
    |  STORE (nested under a campaign)
    ======================================================================*/
    public function store(Request $request, Campaign $campaign)
    {
        $data = $this->validated($request);
        $data['lb_campaign_id'] = $campaign->id;

        $publication = $campaign->publications()->create($data);

        if ($request->expectsJson()) {
            return response()->json(['status' => 'success', 'id' => $publication->id]);
        }

        return back()->with('success', 'Publication added.');
    }

    /*======================================================================
    |  UPDATE
    ======================================================================*/
    public function update(Request $request, Publication $publication)
    {
        $publication->update($this->validated($request));

        if ($request->expectsJson()) {
            return response()->json(['status' => 'success', 'id' => $publication->id]);
        }

        return back()->with('success', 'Publication updated.');
    }

    /*======================================================================
    |  INLINE STATUS UPDATE
    ======================================================================*/
    public function updateStatus(Request $request, Publication $publication)
    {
        $data = $request->validate([
            'status' => ['required', Rule::in(Publication::allStatuses())],
        ]);

        // status_group is recomputed automatically on save (model booted())
        $publication->update($data);

        return response()->json([
            'status'       => 'success',
            'value'        => $publication->status,
            'status_group' => $publication->status_group,
        ]);
    }

    /*======================================================================
    |  SHOW – detail page
    ======================================================================*/
    public function show(Publication $publication)
    {
        $publication->load('campaign.company');

        return view('publications.show', ['publication' => $publication]);
    }

    /*======================================================================
    |  EDIT AJAX – prefill modal
    ======================================================================*/
    public function editAjax(Publication $publication)
    {
        return response()->json(['status' => 'success', 'data' => [
            'id'                   => $publication->id,
            'lb_campaign_id'       => $publication->lb_campaign_id,
            'site'                 => $publication->site,
            'status'               => $publication->status,
            'price'                => (float) $publication->price,
            'live_url'             => $publication->live_url,
            'live_date'            => $publication->live_date?->format('Y-m-d'),
            'date_to_copywriter'   => $publication->date_to_copywriter?->format('Y-m-d'),
            'date_from_copywriter' => $publication->date_from_copywriter?->format('Y-m-d'),
            'date_to_blog'         => $publication->date_to_blog?->format('Y-m-d'),
            'notes'                => $publication->notes,
        ]]);
    }

    /*======================================================================
    |  DESTROY – soft delete
    ======================================================================*/
    public function destroy(Publication $publication)
    {
        $publication->delete();

        if (request()->expectsJson()) {
            return response()->json(['status' => 'success']);
        }

        return back()->with('success', 'Publication deleted.');
    }

    /*======================================================================
    |  Helpers
    ======================================================================*/
    private function validated(Request $request): array
    {
        $data = $request->validate([
            'site'                 => 'required|string|max:255',
            'status'               => ['required', Rule::in(Publication::allStatuses())],
            'price'                => 'nullable|numeric|min:0',
            'live_url'             => 'nullable|url|max:500',
            'live_date'            => 'nullable|date',
            'date_to_copywriter'   => 'nullable|date',
            'date_from_copywriter' => 'nullable|date',
            'date_to_blog'         => 'nullable|date',
            'notes'                => 'nullable|string',
        ]);

        $data['price'] = $data['price'] ?? 0;

        return $data;
    }
}
