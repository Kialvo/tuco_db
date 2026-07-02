<?php

namespace App\Http\Controllers;

use App\Models\Campaign;
use App\Models\Company;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class CampaignController extends Controller
{
    /*======================================================================
    |  INDEX
    ======================================================================*/
    public function index()
    {
        return view('campaigns.index', [
            'users' => User::orderBy('name')->get(['id', 'name']),
        ]);
    }

    /*======================================================================
    |  DATATABLES FEED (server-side)
    ======================================================================*/
    public function getData(Request $request)
    {
        // NOTE: select() must come BEFORE withCount() — withCount appends its
        // count sub-selects, whereas a later select() would wipe them out.
        $q = Campaign::query()
            ->leftJoin('companies', 'companies.id', '=', 'lb_campaigns.company_id')
            ->select('lb_campaigns.*', 'companies.name as company_name')
            ->with(['contact:id,first_name,last_name', 'responsibleUser:id,name'])
            ->withCount([
                'publications',
                'publications as published_count' => fn ($sub) => $sub->where('status', 'Published'),
                'comments',
            ]);

        // Filters
        if ($request->filled('company_id')) {
            $q->where('lb_campaigns.company_id', $request->input('company_id'));
        }
        if ($request->filled('status')) {
            $q->where('lb_campaigns.status', $request->input('status'));
        }
        if ($request->filled('service')) {
            $q->where('lb_campaigns.service', $request->input('service'));
        }
        if ($request->boolean('today')) {
            $q->whereDate('lb_campaigns.next_update_date', now()->toDateString());
        }

        return datatables()->eloquent($q)
            ->addColumn('code_cell', fn (Campaign $c) => $this->codeCell($c))
            ->addColumn('service_badge', fn (Campaign $c) => $this->serviceBadge($c->service))
            ->addColumn('status_badge', fn (Campaign $c) => $this->statusBadge($c))
            ->addColumn('deal', fn (Campaign $c) => '€' . number_format((float) $c->deal_value, 0))
            ->addColumn('pubs', fn (Campaign $c) => $this->pubsCell($c))
            ->addColumn('target', fn (Campaign $c) => $this->targetCell($c))
            ->editColumn('budget_approval_date', fn (Campaign $c) => $c->budget_approval_date?->format('d/m/Y') ?? '—')
            ->editColumn('offer_ready_date', fn (Campaign $c) => $c->offer_ready_date?->format('d/m/Y') ?? '—')
            ->editColumn('deadline', fn (Campaign $c) => $c->deadline?->format('d/m/Y') ?? '—')
            ->editColumn('next_update_date', fn (Campaign $c) => $c->next_update_date?->format('d/m/Y') ?? '—')
            ->addColumn('responsible', fn (Campaign $c) => $this->responsibleCell($c))
            ->addColumn('comments_btn', fn (Campaign $c) => $this->commentsBtn($c))
            ->addColumn('action', fn (Campaign $c) => $this->actionCell($c))
            ->filterColumn('company_name', fn ($query, $keyword) => $query->where('companies.name', 'like', "%{$keyword}%"))
            ->orderColumn('company_name', 'companies.name $1')
            ->rawColumns(['code_cell', 'service_badge', 'status_badge', 'pubs', 'target', 'responsible', 'comments_btn', 'action'])
            ->make(true);
    }

    /*======================================================================
    |  SHOW – detail page
    ======================================================================*/
    public function show(Campaign $campaign)
    {
        $campaign->load([
            'company',
            'contact',
            'responsibleUser',
            'publications' => fn ($q) => $q->orderBy('status_group')->orderBy('id'),
            'comments.user:id,name',
        ]);

        return view('campaigns.show', ['campaign' => $campaign]);
    }

    /*======================================================================
    |  STORE / UPDATE
    ======================================================================*/
    public function store(Request $request)
    {
        $data = $this->validated($request);

        $campaign = Campaign::create($data);

        if ($request->expectsJson()) {
            return response()->json(['status' => 'success', 'id' => $campaign->id]);
        }

        return redirect()->route('crm.campaigns.show', $campaign)->with('success', 'Campaign created.');
    }

    public function update(Request $request, Campaign $campaign)
    {
        $data = $this->validated($request);

        $campaign->update($data);

        if ($request->expectsJson()) {
            return response()->json(['status' => 'success', 'id' => $campaign->id]);
        }

        return redirect()->route('crm.campaigns.show', $campaign)->with('success', 'Campaign updated.');
    }

    /*======================================================================
    |  INLINE STATUS UPDATE
    ======================================================================*/
    public function updateStatus(Request $request, Campaign $campaign)
    {
        $data = $request->validate([
            'status' => ['required', Rule::in(Campaign::allStatuses())],
        ]);

        $campaign->update($data);

        return response()->json(['status' => 'success', 'value' => $campaign->status]);
    }

    /*======================================================================
    |  EDIT AJAX – prefill modal
    ======================================================================*/
    public function editAjax(Campaign $campaign)
    {
        return response()->json(['status' => 'success', 'data' => [
            'id'                   => $campaign->id,
            'code'                 => $campaign->code,
            'company_id'           => $campaign->company_id,
            'company_name'         => $campaign->company?->name,
            'contact_id'           => $campaign->contact_id,
            'contact_name'         => $campaign->contact
                ? trim($campaign->contact->first_name . ' ' . $campaign->contact->last_name)
                : null,
            'responsible_user_id'  => $campaign->responsible_user_id,
            'service'              => $campaign->service,
            'status'               => $campaign->status,
            'deal_value'           => (float) $campaign->deal_value,
            'target_type'          => $campaign->target_type,
            'target_value'         => (float) $campaign->target_value,
            'live_count'           => (float) $campaign->live_count,
            'budget_approval_date' => $campaign->budget_approval_date?->format('Y-m-d'),
            'offer_ready_date'     => $campaign->offer_ready_date?->format('Y-m-d'),
            'deadline'             => $campaign->deadline?->format('Y-m-d'),
            'completion_date'      => $campaign->completion_date?->format('Y-m-d'),
            'next_update_date'     => $campaign->next_update_date?->format('Y-m-d'),
        ]]);
    }

    /*======================================================================
    |  DESTROY – soft delete (also soft-deletes publications)
    ======================================================================*/
    public function destroy(Campaign $campaign)
    {
        $campaign->publications()->delete();
        $campaign->delete();

        if (request()->expectsJson()) {
            return response()->json(['status' => 'success']);
        }

        return redirect()->route('crm.campaigns.index')->with('success', 'Campaign deleted.');
    }

    /*======================================================================
    |  CONTACTS for a company (dependent Select2 in the campaign modal)
    ======================================================================*/
    public function contactsForCompany(Company $company)
    {
        $contacts = $company->clients()->orderBy('first_name')->get(['id', 'first_name', 'last_name']);

        return response()->json([
            'results' => $contacts->map(fn ($c) => [
                'id'   => $c->id,
                'text' => trim($c->first_name . ' ' . $c->last_name) ?: ('Contact #' . $c->id),
            ]),
        ]);
    }

    /*======================================================================
    |  Helpers
    ======================================================================*/
    private function validated(Request $request): array
    {
        $data = $request->validate([
            'code'                 => 'required|string|max:100',
            'company_id'           => 'nullable|exists:companies,id',
            'contact_id'           => 'nullable|exists:clients,id',
            'responsible_user_id'  => 'nullable|exists:users,id',
            'service'              => ['nullable', Rule::in(config('linkbuilding.services'))],
            'status'               => ['required', Rule::in(Campaign::allStatuses())],
            'deal_value'           => 'nullable|numeric|min:0',
            'target_type'          => ['required', Rule::in(array_keys(config('linkbuilding.target_types')))],
            'target_value'         => 'nullable|numeric|min:0',
            'live_count'           => 'nullable|numeric|min:0',
            'budget_approval_date' => 'nullable|date',
            'offer_ready_date'     => 'nullable|date',
            'deadline'             => 'nullable|date',
            'completion_date'      => 'nullable|date',
            'next_update_date'     => 'nullable|date',
        ]);

        $data['deal_value']   = $data['deal_value']   ?? 0;
        $data['target_value'] = $data['target_value'] ?? 0;
        $data['live_count']   = $data['live_count']   ?? 0;

        return $data;
    }

    private function badge(string $text, string $tone, string $extra = ''): string
    {
        $cls = config('linkbuilding.tone_classes')[$tone] ?? 'bg-gray-100 text-gray-700';
        return '<span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-semibold ' . $cls . ' ' . $extra . '">' . e($text) . '</span>';
    }

    private function serviceBadge(?string $service): string
    {
        if (! $service) {
            return '<span class="text-gray-300">—</span>';
        }
        $tone = config('linkbuilding.service_tones')[$service] ?? 'gray';
        return $this->badge($service, $tone);
    }

    private function statusBadge(Campaign $c): string
    {
        $tone = config('linkbuilding.campaign_status_tones')[$c->status] ?? 'gray';
        $cls  = config('linkbuilding.tone_classes')[$tone] ?? 'bg-gray-100 text-gray-700';
        return '<button type="button" class="js-status-badge inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-xs font-semibold ' . $cls . '" '
            . 'data-id="' . $c->id . '" data-status="' . e($c->status) . '">'
            . e($c->status)
            . '<svg class="w-3 h-3 opacity-60" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7"/></svg>'
            . '</button>';
    }

    private function codeCell(Campaign $c): string
    {
        $url = route('crm.campaigns.show', $c->id);
        $company = $c->company_name
            ? ($c->company_id
                ? '<a href="' . route('crm.companies.show', $c->company_id) . '" class="hover:underline">' . e($c->company_name) . '</a>'
                : e($c->company_name))
            : '<span class="text-gray-300">—</span>';

        return '<div>'
            . '<a href="' . $url . '" class="text-green-600 hover:text-green-700 font-medium hover:underline">' . e($c->code) . '</a>'
            . '<div class="text-[11px] text-gray-400 mt-0.5">' . $company . '</div>'
            . '</div>';
    }

    private function pubsCell(Campaign $c): string
    {
        return '<span class="font-semibold text-gray-700">' . (int) $c->published_count . '</span>'
            . '<span class="text-gray-400"> / ' . (int) $c->publications_count . '</span>';
    }

    private function targetCell(Campaign $c): string
    {
        $p = $c->progress;
        if (! $p['has']) {
            return '<span class="text-gray-300">—</span>';
        }
        $bar = ['green' => 'bg-green-500', 'amber' => 'bg-amber-400', 'red' => 'bg-red-400'][$p['tone']] ?? 'bg-gray-300';
        $txt = ['green' => 'text-green-600', 'amber' => 'text-amber-600', 'red' => 'text-red-600'][$p['tone']] ?? 'text-gray-400';

        return '<div class="min-w-[120px]">'
            . '<div class="text-xs text-gray-600">' . e($p['label']) . '</div>'
            . '<div class="mt-1 h-1.5 bg-gray-100 rounded-full overflow-hidden"><div class="h-1.5 ' . $bar . ' rounded-full" style="width:' . $p['pct'] . '%"></div></div>'
            . '<div class="text-[10px] mt-0.5 font-semibold ' . $txt . '">' . e($p['missing']) . '</div>'
            . '</div>';
    }

    private function responsibleCell(Campaign $c): string
    {
        if (! $c->responsibleUser) {
            return '<span class="text-gray-300">—</span>';
        }
        $name = $c->responsibleUser->name;

        return '<div class="inline-flex items-center gap-1.5">'
            . '<span class="inline-flex items-center justify-center w-5 h-5 rounded-full bg-green-100 text-green-700 text-[9px] font-bold">' . e($this->initials($name)) . '</span>'
            . '<span class="text-xs text-gray-700">' . e($name) . '</span>'
            . '</div>';
    }

    private function commentsBtn(Campaign $c): string
    {
        $cnt   = (int) $c->comments_count;
        $badge = $cnt
            ? '<span class="ml-0.5 inline-flex items-center justify-center min-w-[16px] h-4 px-1 rounded-full bg-blue-100 text-blue-700 text-[9px] font-bold">' . $cnt . '</span>'
            : '';

        return '<button type="button" class="js-comments-btn inline-flex items-center gap-1 text-gray-400 hover:text-blue-600 text-xs" data-id="' . $c->id . '" data-code="' . e($c->code) . '">'
            . '<svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"/></svg>'
            . $badge
            . '</button>';
    }

    private function actionCell(Campaign $c): string
    {
        $iconEdit  = '<svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>';
        $iconTrash = '<svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>';

        return '<div class="inline-flex items-center gap-1">'
            . '<button type="button" title="Edit" data-id="' . $c->id . '" class="js-edit-campaign inline-flex items-center justify-center w-7 h-7 rounded-md bg-gray-100 text-gray-700 hover:bg-blue-100 hover:text-blue-700 transition">' . $iconEdit . '</button>'
            . '<button type="button" title="Delete" data-id="' . $c->id . '" data-code="' . e($c->code) . '" class="js-del-campaign inline-flex items-center justify-center w-7 h-7 rounded-md bg-gray-100 text-gray-700 hover:bg-red-100 hover:text-red-700 transition">' . $iconTrash . '</button>'
            . '</div>';
    }

    private function initials(string $name): string
    {
        return mb_strtoupper(
            collect(preg_split('/\s+/', trim($name)))
                ->filter()
                ->map(fn ($w) => mb_substr($w, 0, 1))
                ->take(2)
                ->implode('')
        );
    }
}
