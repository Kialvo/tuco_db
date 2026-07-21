{{-- resources/views/campaigns/show.blade.php --}}
{{-- Phase 3: each publication row IS a `storage` row linked via storage.lb_campaign_id --}}
@extends('layouts.dashboard')
@section('title', $campaign->code)

@php
    use App\Support\PublicationStatus;
    use Illuminate\Support\Carbon;

    $tone     = config('linkbuilding.tone_classes');
    $campTone = fn($s) => $tone[config('linkbuilding.campaign_status_tones')[$s] ?? 'gray'] ?? 'bg-gray-100 text-gray-700';
    $svcTone  = fn($s) => $tone[config('linkbuilding.service_tones')[$s] ?? 'gray'] ?? 'bg-gray-100 text-gray-700';
    $pubTone  = fn($slug) => $tone[PublicationStatus::tone($slug)] ?? 'bg-gray-100 text-gray-700';

    $fd  = fn($d) => $d ? $d->format('d/m/Y') : '—';                                  // campaign date casts
    $fds = fn($v) => $v ? Carbon::parse($v)->format('d/m/Y') : '—';                   // storage raw datetimes
    $ymd = fn($v) => $v ? Carbon::parse($v)->format('Y-m-d') : '';

    $prog      = $campaign->progress;
    $g1        = $campaign->publications->filter(fn($p) => PublicationStatus::group($p->status) === 1);
    $g2        = $campaign->publications->filter(fn($p) => PublicationStatus::group($p->status) === 2);
    $published = $campaign->publications->where('status', 'article_published')->count();
    $f         = $campaign->financials;   // revenue / cost / profit / pct over published publications

    // inline-editable publication cell (data-field = storage column)
    $editable = function ($p, $field, $type, $rawValue, $display) {
        return '<span class="js-pub-edit cursor-pointer rounded px-1 -mx-1 hover:bg-yellow-50 hover:ring-1 hover:ring-yellow-200" '
            . 'data-id="' . $p->id . '" data-field="' . $field . '" data-type="' . $type . '" data-value="' . e((string) ($rawValue ?? '')) . '" title="Click to edit">'
            . $display . '</span>';
    };
@endphp

@section('content')
<div class="px-6 py-6 bg-gray-50 min-h-full">
    <a href="{{ route('crm.campaigns.index') }}" class="inline-flex items-center gap-1.5 text-sm text-gray-500 hover:text-green-600 mb-4">
        <x-icon name="arrow-left" size="sm" /> Back to Campaigns
    </a>

    {{-- Header card --}}
    <div class="bg-white border border-gray-200 rounded-xl shadow-card px-6 py-5 mb-5 flex items-start justify-between flex-wrap gap-3">
        <div>
            <div class="text-2xl font-bold text-gray-900">{{ $campaign->code }}</div>
            <div class="text-sm mt-1">
                @if($campaign->company)
                    <a href="{{ route('crm.companies.show', $campaign->company_id) }}" class="text-green-600 hover:underline font-medium">{{ $campaign->company->name }}</a>
                @else <span class="text-gray-400">No company</span> @endif
            </div>
            @if($campaign->contact)
                <div class="text-xs text-gray-500 mt-1">Contact:
                    <a href="{{ route('crm.clients.show', $campaign->contact_id) }}" class="text-green-600 hover:underline">
                        {{ trim($campaign->contact->first_name.' '.$campaign->contact->last_name) }}
                    </a>
                </div>
            @endif
        </div>
        <div class="flex items-center gap-2 flex-wrap">
            <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-semibold {{ $campTone($campaign->status) }}">{{ $campaign->status }}</span>
            @if($campaign->service)
                <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-semibold {{ $svcTone($campaign->service) }}">{{ $campaign->service }}</span>
            @endif
            <button id="btnEditCampaign" class="inline-flex items-center gap-1.5 px-3 py-2 text-sm font-semibold text-gray-600 bg-white border border-gray-300 rounded-lg hover:bg-gray-50">
                <x-icon name="pencil" size="sm" /> Edit
            </button>
            <button id="btnLinkPub" class="inline-flex items-center gap-1.5 px-3 py-2 text-sm font-semibold text-green-700 bg-white border border-green-300 rounded-lg hover:bg-green-50">
                <x-icon name="link" size="sm" /> Add Existing Publication
            </button>
            <button id="btnNewPub" class="inline-flex items-center gap-1.5 px-3 py-2 text-sm font-semibold text-white bg-green-600 hover:bg-green-700 rounded-lg shadow-sm">
                <x-icon name="plus" size="sm" /> New Publication
            </button>
        </div>
    </div>

    {{-- Stat cards --}}
    <div class="grid grid-cols-2 md:grid-cols-4 lg:grid-cols-6 gap-3 mb-5">
        @php
            $stat = function ($label, $val) {
                return '<div class="bg-white border border-gray-200 rounded-xl shadow-card px-4 py-3">'
                    . '<div class="text-[10px] uppercase tracking-wide text-gray-400 font-semibold mb-1">' . $label . '</div>'
                    . '<div class="text-sm font-bold text-gray-800">' . $val . '</div></div>';
            };
        @endphp
        {!! $stat('Target', $campaign->target_type === 'budget' ? '€'.number_format((float)$campaign->target_value,0) : (int)$campaign->target_value.' pubs') !!}
        <div class="bg-white border border-gray-200 rounded-xl shadow-card px-4 py-3">
            <div class="text-[10px] uppercase tracking-wide text-gray-400 font-semibold mb-1">Progress</div>
            @if($prog['has'])
                <div class="text-xs text-gray-700">{{ $prog['label'] }}</div>
                <div class="mt-1 h-1.5 bg-gray-100 rounded-full overflow-hidden">
                    <div class="h-1.5 rounded-full {{ ['green'=>'bg-green-500','amber'=>'bg-amber-400','red'=>'bg-red-400'][$prog['tone']] ?? 'bg-gray-300' }}" style="width: {{ $prog['pct'] }}%"></div>
                </div>
            @else <div class="text-sm font-bold text-gray-400">—</div> @endif
        </div>
        {!! $stat('Published', $published . ' pub' . ($published != 1 ? 's' : '')) !!}
        {!! $stat('Deal Value', '€'.number_format((float)$campaign->deal_value,0)) !!}
        {!! $stat('Revenues', '€'.number_format($f['revenue'],0)) !!}
        {!! $stat('Costs', '€'.number_format($f['cost'],0)) !!}
        {!! $stat('Profit €', ($f['profit'] < 0 ? '-€' : '€').number_format(abs($f['profit']),0)) !!}
        {!! $stat('Profit %', is_null($f['pct']) ? '—' : $f['pct'].'%') !!}
        {!! $stat('Offer Ready', $fd($campaign->offer_ready_date)) !!}
        {!! $stat('Deadline', $fd($campaign->deadline)) !!}
        {!! $stat('Next Update', $fd($campaign->next_update_date) . ($campaign->responsibleUser ? '<span class="block text-[10px] text-gray-400 font-normal">'.e($campaign->responsibleUser->name).'</span>' : '')) !!}
        {!! $stat('Completion', $fd($campaign->liveCompletionDate())) !!}
    </div>

    {{-- Publications --}}
    <div class="bg-white border border-gray-200 rounded-xl shadow-card mb-5">
        <div class="flex items-center justify-between px-5 py-3 border-b border-gray-100">
            <div class="text-xs font-bold uppercase tracking-wide text-gray-600">Publications <span class="ml-1 text-gray-400">{{ $campaign->publications->count() }}</span></div>
            <div class="text-xs text-gray-500">{{ $published }} published</div>
        </div>

        @if($campaign->publications->isEmpty())
            <div class="text-center py-10 text-gray-400">
                <div class="text-sm">No publications yet</div>
                <div class="text-xs mt-1">Create one from scratch or link an existing Storage entry.</div>
            </div>
        @else
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead>
                    <tr class="bg-gray-50 text-[10px] uppercase tracking-wider text-gray-500">
                        <th class="text-left py-2.5 px-3 font-semibold">Publisher</th>
                        <th class="text-left py-2.5 px-3 font-semibold">Status</th>
                        <th class="text-right py-2.5 px-3 font-semibold">Price €</th>
                        <th class="text-left py-2.5 px-3 font-semibold">Sent&nbsp;to&nbsp;Copy</th>
                        <th class="text-left py-2.5 px-3 font-semibold">Copy&nbsp;Received</th>
                        <th class="text-left py-2.5 px-3 font-semibold">Sent&nbsp;to&nbsp;Blog</th>
                        <th class="text-left py-2.5 px-3 font-semibold">Live&nbsp;URL</th>
                        <th class="text-left py-2.5 px-3 font-semibold">Live&nbsp;Date</th>
                        <th class="py-2.5 px-3 text-right font-semibold">Actions</th>
                    </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @foreach([['GROUP 1 – Site Evaluation', $g1], ['GROUP 2 – Production', $g2]] as [$label, $rows])
                            @if($rows->count())
                                <tr><td colspan="9" class="bg-gray-50/70 text-[10px] font-bold uppercase tracking-wider text-gray-400 px-3 py-1.5">{{ $label }}</td></tr>
                                @foreach($rows as $p)
                                    <tr class="hover:bg-gray-50">
                                        <td class="py-2.5 px-3 font-medium">
                                            <a href="{{ route('storages.edit', $p->id) }}" class="text-green-600 hover:underline" title="Open full Storage record">{{ $p->publisher_domain ?: '—' }}</a>
                                        </td>
                                        <td class="py-2.5 px-3">
                                            <button type="button" class="js-pub-status inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-xs font-semibold {{ $pubTone($p->status) }}" data-id="{{ $p->id }}">
                                                {{ $p->status_label ?? '—' }}
                                                <svg class="w-3 h-3 opacity-60" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7"/></svg>
                                            </button>
                                        </td>
                                        <td class="py-2.5 px-3 text-right whitespace-nowrap">{!! $editable($p, 'price', 'money', (float)$p->total_revenues, '€'.number_format((float)$p->total_revenues, 0)) !!}</td>
                                        <td class="py-2.5 px-3 text-gray-500 whitespace-nowrap">{!! $editable($p, 'copywriter_commision_date', 'date', $ymd($p->copywriter_commision_date), $fds($p->copywriter_commision_date)) !!}</td>
                                        <td class="py-2.5 px-3 text-gray-500 whitespace-nowrap">{!! $editable($p, 'copywriter_submission_date', 'date', $ymd($p->copywriter_submission_date), $fds($p->copywriter_submission_date)) !!}</td>
                                        <td class="py-2.5 px-3 text-gray-500 whitespace-nowrap">{!! $editable($p, 'article_sent_to_publisher', 'date', $ymd($p->article_sent_to_publisher), $fds($p->article_sent_to_publisher)) !!}</td>
                                        <td class="py-2.5 px-3">{!! $editable($p, 'article_url', 'text', $p->article_url, $p->article_url ? '<span class="text-green-600 text-xs">'.e(\Illuminate\Support\Str::of($p->article_url)->replace(['https://','http://'],'')->limit(24)).'</span>' : '<span class="text-gray-300">—</span>') !!}</td>
                                        <td class="py-2.5 px-3 text-gray-500 whitespace-nowrap">{!! $editable($p, 'publication_date', 'date', $ymd($p->publication_date), $fds($p->publication_date)) !!}</td>
                                        <td class="py-2.5 px-3 text-right whitespace-nowrap">
                                            <button type="button" class="js-pub-comments inline-flex items-center justify-center h-7 px-1.5 rounded-md text-gray-400 hover:bg-blue-50 hover:text-blue-600" data-id="{{ $p->id }}" data-site="{{ $p->publisher_domain }}" title="Conversation">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"/></svg>
                                            </button>
                                            <a href="{{ route('storages.edit', $p->id) }}" class="inline-flex items-center justify-center w-7 h-7 rounded-md bg-gray-100 text-gray-700 hover:bg-green-100 hover:text-green-700" title="More details (Storage record)">
                                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/></svg>
                                            </a>
                                            <button type="button" class="js-edit-pub inline-flex items-center justify-center w-7 h-7 rounded-md bg-gray-100 text-gray-700 hover:bg-blue-100 hover:text-blue-700" data-id="{{ $p->id }}" title="Quick edit">
                                                <x-icon name="pencil" size="sm" />
                                            </button>
                                            <button type="button" class="js-unlink-pub inline-flex items-center justify-center w-7 h-7 rounded-md bg-gray-100 text-gray-700 hover:bg-red-100 hover:text-red-700" data-id="{{ $p->id }}" data-site="{{ $p->publisher_domain }}" title="Unlink from campaign">
                                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M13.828 10.172a4 4 0 010 5.656l-3 3a4 4 0 01-5.656-5.656l1.5-1.5M10.172 13.828a4 4 0 010-5.656l3-3a4 4 0 015.656 5.656l-1.5 1.5M4 4l16 16"/></svg>
                                            </button>
                                        </td>
                                    </tr>
                                @endforeach
                            @endif
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>

</div>

{{-- ═══════════ Publication modal (create / quick edit) ═══════════ --}}
<div id="pubModal" class="fixed inset-0 z-50 hidden items-center justify-center bg-black/50 p-4">
    <div class="bg-white border border-gray-200 rounded-2xl shadow-2xl w-full max-w-lg relative max-h-[92vh] overflow-y-auto">
        <div class="flex items-center justify-between px-6 py-4 border-b border-gray-100 sticky top-0 bg-white">
            <h2 id="pubModalTitle" class="text-lg font-bold text-gray-800">New Publication</h2>
            <button type="button" class="js-close-pub text-gray-400 hover:text-gray-600"><x-icon name="x" size="sm" /></button>
        </div>
        <div id="pubErrors" class="hidden mx-6 mt-4 bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded text-sm"></div>
        <form id="pubForm" class="px-6 py-5 space-y-4">
            <input type="hidden" id="p_id">
            <div>
                <label class="block text-xs font-semibold text-gray-600 mb-1">Publisher / Website <span class="text-red-500">*</span></label>
                <select id="p_site" class="block w-full border border-gray-300 rounded-md text-sm" style="width:100%"></select>
                <p class="text-[11px] text-gray-400 mt-1">Search the Domains catalog, or type a new domain and press Enter.</p>
            </div>
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-xs font-semibold text-gray-600 mb-1">Status <span class="text-red-500">*</span></label>
                    <select id="p_status" class="block w-full border border-gray-300 rounded-md text-sm px-3 py-2 bg-white focus:ring-green-500 focus:border-green-500">
                        @foreach(PublicationStatus::grouped() as $group => $statuses)
                            <optgroup label="{{ $group }}">
                                @foreach($statuses as $slug => $lbl)<option value="{{ $slug }}">{{ $lbl }}</option>@endforeach
                            </optgroup>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-semibold text-gray-600 mb-1">Price (€) <span class="text-red-500">*</span></label>
                    <input type="number" step="0.01" min="0" id="p_price" class="block w-full border border-gray-300 rounded-md text-sm px-3 py-2 focus:ring-green-500 focus:border-green-500" placeholder="0">
                </div>
            </div>
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-xs font-semibold text-gray-600 mb-1">Live URL</label>
                    <input type="text" id="p_article_url" class="block w-full border border-gray-300 rounded-md text-sm px-3 py-2 focus:ring-green-500 focus:border-green-500" placeholder="https://…">
                </div>
                <div>
                    <label class="block text-xs font-semibold text-gray-600 mb-1">Live Date</label>
                    <input type="text" id="p_publication_date" class="js-date block w-full border border-gray-300 rounded-md text-sm px-3 py-2 focus:ring-green-500 focus:border-green-500" placeholder="YYYY-MM-DD">
                </div>
            </div>
            <div class="pt-2 text-[11px] font-bold uppercase tracking-wide text-gray-400 border-t border-gray-100">Production Dates</div>
            <div class="grid grid-cols-3 gap-3">
                <div>
                    <label class="block text-xs font-semibold text-gray-600 mb-1">Sent to Copy</label>
                    <input type="text" id="p_copywriter_commision_date" class="js-date block w-full border border-gray-300 rounded-md text-sm px-3 py-2 focus:ring-green-500 focus:border-green-500" placeholder="YYYY-MM-DD">
                </div>
                <div>
                    <label class="block text-xs font-semibold text-gray-600 mb-1">Copy Received</label>
                    <input type="text" id="p_copywriter_submission_date" class="js-date block w-full border border-gray-300 rounded-md text-sm px-3 py-2 focus:ring-green-500 focus:border-green-500" placeholder="YYYY-MM-DD">
                </div>
                <div>
                    <label class="block text-xs font-semibold text-gray-600 mb-1">Sent to Blog</label>
                    <input type="text" id="p_article_sent_to_publisher" class="js-date block w-full border border-gray-300 rounded-md text-sm px-3 py-2 focus:ring-green-500 focus:border-green-500" placeholder="YYYY-MM-DD">
                </div>
            </div>
            <p class="text-[11px] text-gray-400">All other fields (accounting, invoicing, copywriter…) are edited on the full Storage record — use “More details” on the row after saving.</p>
        </form>
        <div class="flex justify-end gap-2 px-6 py-4 border-t border-gray-100 sticky bottom-0 bg-white">
            <button type="button" class="js-close-pub px-4 py-2 text-sm font-semibold text-gray-600 bg-white border border-gray-300 rounded-lg hover:bg-gray-50">Cancel</button>
            <button type="button" id="p_save" class="px-4 py-2 text-sm font-semibold text-white bg-green-600 hover:bg-green-700 rounded-lg shadow-sm">Add Publication</button>
        </div>
    </div>
</div>

{{-- ═══════════ Link-existing modal ═══════════ --}}
<div id="linkModal" class="fixed inset-0 z-50 hidden items-center justify-center bg-black/50 p-4">
    <div class="bg-white border border-gray-200 rounded-2xl shadow-2xl w-full max-w-xl relative">
        <div class="flex items-center justify-between px-6 py-4 border-b border-gray-100">
            <h2 class="text-lg font-bold text-gray-800">Add Existing Publication</h2>
            <button type="button" class="js-close-link text-gray-400 hover:text-gray-600"><x-icon name="x" size="sm" /></button>
        </div>
        <div id="linkErrors" class="hidden mx-6 mt-4 bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded text-sm"></div>
        <div class="px-6 py-5 space-y-3">
            <label class="block text-xs font-semibold text-gray-600 mb-1">Search Storage by domain, article URL or ID</label>
            <select id="linkSelect" class="block w-full border border-gray-300 rounded-md text-sm" multiple="multiple" style="width:100%"></select>
            <label class="inline-flex items-center gap-2 text-xs text-gray-500 mt-1">
                <input type="checkbox" id="linkIncludeAssigned" class="rounded border-gray-300 text-green-600 focus:ring-green-500">
                Also show rows already assigned to another campaign (linking will move them here)
            </label>
        </div>
        <div class="flex justify-end gap-2 px-6 py-4 border-t border-gray-100">
            <button type="button" class="js-close-link px-4 py-2 text-sm font-semibold text-gray-600 bg-white border border-gray-300 rounded-lg hover:bg-gray-50">Cancel</button>
            <button type="button" id="linkSave" class="px-4 py-2 text-sm font-semibold text-white bg-green-600 hover:bg-green-700 rounded-lg shadow-sm">Add Selected</button>
        </div>
    </div>
</div>

{{-- ═══════════ Edit-campaign modal ═══════════ --}}
<div id="campaignModal" class="fixed inset-0 z-50 hidden items-center justify-center bg-black/50 p-4">
    <div class="bg-white border border-gray-200 rounded-2xl shadow-2xl w-full max-w-2xl relative max-h-[92vh] overflow-y-auto">
        <div class="flex items-center justify-between px-6 py-4 border-b border-gray-100 sticky top-0 bg-white z-10">
            <h2 class="text-lg font-bold text-gray-800">Edit Campaign</h2>
            <button type="button" class="js-close-campaign text-gray-400 hover:text-gray-600"><x-icon name="x" size="sm" /></button>
        </div>
        <div id="campaignErrors" class="hidden mx-6 mt-4 bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded text-sm"></div>
        <form id="campaignForm" class="px-6 py-5 space-y-4">
            <input type="hidden" id="c_company_id_hidden">
            <input type="hidden" id="c_contact_id_hidden">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div><label class="block text-xs font-semibold text-gray-600 mb-1">Campaign Code <span class="text-red-500">*</span></label><input type="text" id="c_code" class="block w-full border border-gray-300 rounded-md text-sm px-3 py-2 focus:ring-green-500 focus:border-green-500"></div>
                <div>
                    <label class="block text-xs font-semibold text-gray-600 mb-1">Service</label>
                    <select id="c_service" class="block w-full border border-gray-300 rounded-md text-sm px-3 py-2 bg-white focus:ring-green-500 focus:border-green-500">
                        <option value="">—</option>
                        @foreach(config('linkbuilding.services') as $s)<option value="{{ $s }}">{{ $s }}</option>@endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-semibold text-gray-600 mb-1">Status <span class="text-red-500">*</span></label>
                    <select id="c_status" class="block w-full border border-gray-300 rounded-md text-sm px-3 py-2 bg-white focus:ring-green-500 focus:border-green-500">
                        @foreach($campaign::statusGroups() as $group => $statuses)
                            <optgroup label="{{ $group }}">@foreach($statuses as $st)<option value="{{ $st }}">{{ $st }}</option>@endforeach</optgroup>
                        @endforeach
                    </select>
                </div>
                <div><label class="block text-xs font-semibold text-gray-600 mb-1">Deal Value (€)</label><input type="number" step="0.01" id="c_deal_value" class="block w-full border border-gray-300 rounded-md text-sm px-3 py-2 focus:ring-green-500 focus:border-green-500"></div>
                <div>
                    <label class="block text-xs font-semibold text-gray-600 mb-1">Target Type</label>
                    <select id="c_target_type" class="block w-full border border-gray-300 rounded-md text-sm px-3 py-2 bg-white focus:ring-green-500 focus:border-green-500">
                        @foreach(config('linkbuilding.target_types') as $val => $lbl)<option value="{{ $val }}">{{ $lbl }}</option>@endforeach
                    </select>
                </div>
                <div><label id="c_target_label" class="block text-xs font-semibold text-gray-600 mb-1">Target Amount (€)</label><input type="number" step="0.01" id="c_target_value" class="block w-full border border-gray-300 rounded-md text-sm px-3 py-2 focus:ring-green-500 focus:border-green-500"></div>
            </div>
            <div class="pt-2 text-[11px] font-bold uppercase tracking-wide text-gray-400 border-t border-gray-100">Dates</div>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div><label class="block text-xs font-semibold text-gray-600 mb-1">Budget Approval</label><input type="text" id="c_budget_approval_date" class="js-date block w-full border border-gray-300 rounded-md text-sm px-3 py-2 focus:ring-green-500 focus:border-green-500"></div>
                <div><label class="block text-xs font-semibold text-gray-600 mb-1">Offer Ready</label><input type="text" id="c_offer_ready_date" class="js-date block w-full border border-gray-300 rounded-md text-sm px-3 py-2 focus:ring-green-500 focus:border-green-500"></div>
                <div><label class="block text-xs font-semibold text-gray-600 mb-1">Deadline</label><input type="text" id="c_deadline" class="js-date block w-full border border-gray-300 rounded-md text-sm px-3 py-2 focus:ring-green-500 focus:border-green-500"></div>
                <div><label class="block text-xs font-semibold text-gray-600 mb-1">Completion <span class="text-gray-400 font-normal">(auto)</span></label><input type="text" id="c_completion_date" readonly placeholder="—" class="block w-full border border-gray-300 rounded-md text-sm px-3 py-2 bg-gray-100 text-gray-500 cursor-not-allowed"></div>
            </div>
            <div class="pt-2 text-[11px] font-bold uppercase tracking-wide text-gray-400 border-t border-gray-100">Assignment</div>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div><label class="block text-xs font-semibold text-gray-600 mb-1">Next Update</label><input type="text" id="c_next_update_date" class="js-date block w-full border border-gray-300 rounded-md text-sm px-3 py-2 focus:ring-green-500 focus:border-green-500"></div>
                <div>
                    <label class="block text-xs font-semibold text-gray-600 mb-1">Responsible</label>
                    <select id="c_responsible_user_id" class="block w-full border border-gray-300 rounded-md text-sm px-3 py-2 bg-white focus:ring-green-500 focus:border-green-500">
                        <option value="">—</option>
                        @foreach(\App\Models\User::whereIn('role',['admin','editor'])->orderBy('name')->get(['id','name']) as $u)<option value="{{ $u->id }}">{{ $u->name }}</option>@endforeach
                    </select>
                </div>
            </div>
        </form>
        <div class="flex justify-end gap-2 px-6 py-4 border-t border-gray-100 sticky bottom-0 bg-white">
            <button type="button" class="js-close-campaign px-4 py-2 text-sm font-semibold text-gray-600 bg-white border border-gray-300 rounded-lg hover:bg-gray-50">Cancel</button>
            <button type="button" id="c_save" class="px-4 py-2 text-sm font-semibold text-white bg-green-600 hover:bg-green-700 rounded-lg shadow-sm">Save Changes</button>
        </div>
    </div>
</div>

{{-- Publication comments now open in the CRM-style conversation pane (layout partial) --}}

{{-- floating publication status menu --}}
<div id="pubStatusMenu" class="hidden fixed z-[60] bg-white border border-gray-200 rounded-lg shadow-xl py-1 max-h-72 overflow-y-auto min-w-[240px] text-sm"></div>
@endsection

@push('scripts')
<script>
$(function () {
    const csrf = $('meta[name="csrf-token"]').attr('content');
    const CAMPAIGN_ID = {{ $campaign->id }};
    const PUB_STATUSES = @json(PublicationStatus::grouped()); {{-- group label => {slug: label} --}}

    // Position a fixed dropdown near a trigger, flipping up if it would overflow the viewport bottom.
    function positionMenu($menu, rect) {
        $menu.removeClass('hidden');
        const mh = $menu.outerHeight(), mw = $menu.outerWidth();
        const top = (rect.bottom + mh > window.innerHeight - 8) ? Math.max(8, rect.top - mh - 4) : rect.bottom + 4;
        const left = Math.max(8, Math.min(rect.left, window.innerWidth - mw - 8));
        $menu.css({ top: top + 'px', left: left + 'px' });
    }

    flatpickr('.js-date', { dateFormat: 'Y-m-d', allowInput: true });

    /* ── Publisher select2 (Domains catalog search + free-text tags) ── */
    function initSiteSelect() {
        $('#p_site').select2({
            dropdownParent: $('#pubModal'),
            width: '100%',
            placeholder: 'Search domain…',
            allowClear: true,
            tags: true,
            createTag: p => {
                const term = $.trim(p.term);
                return term === '' ? null : { id: term, text: term + ' (new)', newTag: true };
            },
            ajax: {
                url: "{{ route('crm.publications.websitesSearch') }}",
                dataType: 'json',
                delay: 250,
                data: p => ({ q: p.term }),
                processResults: data => ({
                    results: data.results.map(r => ({ id: 'w' + r.id, text: r.text }))
                })
            }
        });
    }
    initSiteSelect();

    /* ── New/Edit Publication modal ── */
    const pubModal = $('#pubModal');
    const PUB_DATE_FIELDS = ['publication_date', 'copywriter_commision_date', 'copywriter_submission_date', 'article_sent_to_publisher'];
    function setPubDate(f, v) {
        const el = document.getElementById('p_' + f);
        if (el && el._flatpickr) { v ? el._flatpickr.setDate(v, true) : el._flatpickr.clear(); }
        else if (el) el.value = v || '';
    }
    function openPub() { pubModal.removeClass('hidden').addClass('flex'); }
    function closePub() { pubModal.addClass('hidden').removeClass('flex'); }
    $('.js-close-pub').on('click', closePub);
    pubModal.on('click', e => { if (e.target === pubModal[0]) closePub(); });

    function resetPub() {
        $('#pubErrors').addClass('hidden').empty();
        $('#p_id').val('');
        $('#p_price,#p_article_url').val('');
        $('#p_site').empty().val(null).trigger('change');
        $('#p_status').prop('selectedIndex', 0);
        PUB_DATE_FIELDS.forEach(f => setPubDate(f, ''));
    }

    $('#btnNewPub').on('click', function () {
        resetPub();
        $('#pubModalTitle').text('New Publication');
        $('#p_save').text('Add Publication');
        openPub();
    });

    $(document).on('click', '.js-edit-pub', function () {
        const id = $(this).data('id');
        resetPub();
        $('#pubModalTitle').text('Edit Publication');
        $('#p_save').text('Save Changes');
        $.get("{{ url('publications') }}/" + id + "/edit-ajax", function (res) {
            const d = res.data;
            $('#p_id').val(d.id);
            // preselect publisher: existing website (w<id>) or legacy free text
            if (d.website_id) {
                $('#p_site').append(new Option(d.site || ('website #' + d.website_id), 'w' + d.website_id, true, true)).trigger('change');
            } else if (d.site) {
                $('#p_site').append(new Option(d.site, d.site, true, true)).trigger('change');
            }
            $('#p_status').val(d.status || '');
            $('#p_price').val(d.price || '');
            $('#p_article_url').val(d.article_url || '');
            PUB_DATE_FIELDS.forEach(f => setPubDate(f, d[f]));
            openPub();
        }).fail(() => alert('Unable to load publication.'));
    });

    // Client-side required check: red border + message, no request sent.
    function markInvalid($el, bad) {
        ($el.hasClass('select2-hidden-accessible') ? $el.next('.select2').find('.select2-selection') : $el)
            .toggleClass('border-red-500 ring-1 ring-red-300', bad);
    }
    function requireFields(pairs, $errorBox) {
        const missing = [];
        pairs.forEach(([$el, label]) => {
            const bad = !(($el.val() || '').toString().trim());
            markInvalid($el, bad);
            if (bad) missing.push(label);
        });
        if (missing.length) {
            $errorBox.html('Required: ' + missing.join(', ') + '.').removeClass('hidden');
            return false;
        }
        $errorBox.addClass('hidden').empty();
        return true;
    }

    $('#p_save').on('click', function () {
        $('#pubErrors').addClass('hidden').empty();
        if (!requireFields([
            [$('#p_site'), 'Publisher / Website'],
            [$('#p_status'), 'Status'],
            [$('#p_price'), 'Price'],
        ], $('#pubErrors'))) return;

        const id = $('#p_id').val();
        const siteVal = $('#p_site').val() || '';
        const payload = {
            status: $('#p_status').val(),
            price: $('#p_price').val(),
            article_url: $('#p_article_url').val() || '',
            publication_date: $('#p_publication_date').val() || '',
            copywriter_commision_date: $('#p_copywriter_commision_date').val() || '',
            copywriter_submission_date: $('#p_copywriter_submission_date').val() || '',
            article_sent_to_publisher: $('#p_article_sent_to_publisher').val() || '',
            _token: csrf
        };
        if (/^w\d+$/.test(siteVal)) { payload.website_id = siteVal.slice(1); }
        else { payload.site = siteVal; }

        let url;
        if (id) { url = "{{ url('publications') }}/" + id; payload._method = 'PUT'; }
        else { url = "{{ url('campaigns') }}/" + CAMPAIGN_ID + "/publications"; }

        $.ajax({
            url, method: 'POST', data: payload, headers: { 'Accept': 'application/json' },
            success: function () { location.reload(); },
            error: function (xhr) {
                const errs = xhr.responseJSON?.errors ?? {};
                $('#pubErrors').html(Object.values(errs).flat().join('<br>') || 'An error occurred.').removeClass('hidden');
            }
        });
    });

    /* ── Unlink publication (storage row survives in Storage) ── */
    $(document).on('click', '.js-unlink-pub', function () {
        const id = $(this).data('id'), site = $(this).data('site') || 'this publication';
        Swal.fire({
            icon: 'warning',
            title: 'Unlink from campaign?',
            text: site + ' stays in Storage — it is only removed from this campaign.',
            showCancelButton: true, confirmButtonText: 'Unlink', confirmButtonColor: '#dc2626'
        }).then(r => {
            if (!r.isConfirmed) return;
            $.ajax({ url: "{{ url('publications') }}/" + id, method: 'POST', data: { _method: 'DELETE', _token: csrf }, headers: { 'Accept': 'application/json' },
                success: () => location.reload() });
        });
    });

    /* ── Link-existing modal ── */
    const linkModal = $('#linkModal');
    function initLinkSelect() {
        $('#linkSelect').select2({
            dropdownParent: linkModal,
            width: '100%',
            placeholder: 'Type to search Storage…',
            multiple: true,
            ajax: {
                url: "{{ url('campaigns') }}/" + CAMPAIGN_ID + "/storage-search",
                dataType: 'json',
                delay: 250,
                data: p => ({ q: p.term, include_assigned: $('#linkIncludeAssigned').is(':checked') ? 1 : 0 }),
                processResults: data => ({ results: data.results })
            },
            templateResult: r => {
                if (!r.id) return r.text;
                const parts = [
                    '<div class="text-sm font-medium">' + $('<i/>').text(r.domain || 'no domain').html()
                        + ' <span class="text-gray-400 font-normal">#' + r.id + '</span></div>',
                    '<div class="text-xs text-gray-500">' + $('<i/>').text(r.status || '—').html()
                        + ' · €' + (r.price || 0)
                        + (r.pub_date ? ' · ' + r.pub_date : '')
                        + (r.campaign ? ' · <span class="text-amber-600">in ' + $('<i/>').text(r.campaign).html() + '</span>' : '')
                        + '</div>'
                ];
                return $('<div>' + parts.join('') + '</div>');
            }
        });
    }
    initLinkSelect();
    $('#linkIncludeAssigned').on('change', function () {
        $('#linkSelect').empty().val(null).trigger('change');   // re-query with the new scope
    });

    $('#btnLinkPub').on('click', function () {
        $('#linkErrors').addClass('hidden').empty();
        $('#linkSelect').empty().val(null).trigger('change');
        linkModal.removeClass('hidden').addClass('flex');
    });
    $('.js-close-link').on('click', () => linkModal.addClass('hidden').removeClass('flex'));
    linkModal.on('click', e => { if (e.target === linkModal[0]) linkModal.addClass('hidden').removeClass('flex'); });

    $('#linkSave').on('click', function () {
        const ids = $('#linkSelect').val() || [];
        if (!ids.length) {
            $('#linkErrors').text('Select at least one Storage entry.').removeClass('hidden');
            return;
        }
        $.ajax({
            url: "{{ url('campaigns') }}/" + CAMPAIGN_ID + "/link-publications",
            method: 'POST', data: { ids: ids, _token: csrf }, headers: { 'Accept': 'application/json' },
            success: () => location.reload(),
            error: function (xhr) {
                const errs = xhr.responseJSON?.errors ?? {};
                $('#linkErrors').html(Object.values(errs).flat().join('<br>') || 'An error occurred.').removeClass('hidden');
            }
        });
    });

    /* ── Inline publication field edit ── */
    $(document).on('click', '.js-pub-edit', function () {
        const cell = $(this);
        if (cell.hasClass('editing')) return;
        const id = cell.data('id'), field = cell.data('field'), type = cell.data('type');
        const cur = cell.attr('data-value') || '';
        cell.addClass('editing');

        let input;
        if (type === 'date') input = $('<input type="date" class="border border-gray-300 rounded px-1 py-0.5 text-xs w-36">').val(cur);
        else if (type === 'text') input = $('<input type="text" class="border border-gray-300 rounded px-1 py-0.5 text-xs w-44" placeholder="https://…">').val(cur);
        else input = $('<input type="number" min="0" step="0.01" class="border border-gray-300 rounded px-1 py-0.5 text-xs w-24">').val(cur);
        cell.empty().append(input);
        input.trigger('focus');
        input.on('click', ev => ev.stopPropagation());

        let done = false;
        function commit(save) {
            if (done) return; done = true;
            if (!save) { location.reload(); return; }
            $.ajax({
                url: "{{ url('publications') }}/" + id + "/inline", method: 'PUT',
                data: { field: field, value: input.val() }, headers: { 'X-CSRF-TOKEN': csrf, 'Accept': 'application/json' },
                success: () => location.reload(),
                error: (xhr) => {
                    const msg = Object.values(xhr.responseJSON?.errors ?? {}).flat().join(' ') || 'Update failed.';
                    Swal.fire({ icon: 'error', title: 'Invalid value', text: msg, timer: 2600, showConfirmButton: false });
                    location.reload();
                }
            });
        }
        input.on('keydown', function (ev) {
            if (ev.key === 'Enter') { ev.preventDefault(); commit(true); }
            else if (ev.key === 'Escape') { commit(false); }
        });
        input.on('blur', () => commit(true));
    });

    /* ── Inline publication status (unified slug list) ── */
    const menu = $('#pubStatusMenu');
    let pubTargetId = null;
    (function build() {
        let h = '';
        $.each(PUB_STATUSES, function (group, items) {
            h += '<div class="px-3 pt-2 pb-1 text-[9px] uppercase tracking-wider text-gray-400 font-bold">' + group + '</div>';
            $.each(items, function (slug, label) {
                h += '<div class="js-pub-opt px-3 py-1.5 hover:bg-gray-50 cursor-pointer" data-status="' + slug + '">' + label + '</div>';
            });
        });
        menu.html(h);
    })();
    $(document).on('click', '.js-pub-status', function (e) {
        e.stopPropagation();
        pubTargetId = $(this).data('id');
        const r = this.getBoundingClientRect();
        positionMenu(menu, r);
    });
    $(document).on('click', '.js-pub-opt', function () {
        $.ajax({ url: "{{ url('publications') }}/" + pubTargetId + "/status", method: 'PUT', data: { status: $(this).data('status') }, headers: { 'X-CSRF-TOKEN': csrf }, success: () => location.reload() });
    });
    $(document).on('click', () => menu.addClass('hidden'));

    /* ── Edit campaign modal ── */
    const campaignModal = $('#campaignModal');
    $('.js-close-campaign').on('click', () => campaignModal.addClass('hidden').removeClass('flex'));
    campaignModal.on('click', e => { if (e.target === campaignModal[0]) campaignModal.addClass('hidden').removeClass('flex'); });
    function updTargetLabel() { $('#c_target_label').text($('#c_target_type').val() === 'budget' ? 'Target Amount (€)' : 'Nr. of Publications'); }
    $('#c_target_type').on('change', updTargetLabel);
    const C_DATES = ['budget_approval_date', 'offer_ready_date', 'deadline', 'completion_date', 'next_update_date'];
    function setCDate(f, v) { const el = document.getElementById('c_' + f); if (el && el._flatpickr) { v ? el._flatpickr.setDate(v, true) : el._flatpickr.clear(); } else if (el) el.value = v || ''; }

    $('#btnEditCampaign').on('click', function () {
        $('#campaignErrors').addClass('hidden').empty();
        $.get("{{ url('campaigns') }}/" + CAMPAIGN_ID + "/edit-ajax", function (res) {
            const d = res.data;
            $('#c_code').val(d.code);
            $('#c_company_id_hidden').val(d.company_id || '');
            $('#c_contact_id_hidden').val(d.contact_id || '');
            $('#c_service').val(d.service || '');
            $('#c_status').val(d.status);
            $('#c_deal_value').val(d.deal_value || '');
            $('#c_target_type').val(d.target_type || 'budget'); updTargetLabel();
            $('#c_target_value').val(d.target_value || '');
            $('#c_responsible_user_id').val(d.responsible_user_id || '');
            C_DATES.forEach(f => setCDate(f, d[f]));
            campaignModal.removeClass('hidden').addClass('flex');
        }).fail(() => alert('Unable to load campaign.'));
    });

    $('#c_save').on('click', function () {
        $('#campaignErrors').addClass('hidden').empty();
        if (!requireFields([
            [$('#c_code'), 'Campaign Code'],
            [$('#c_status'), 'Status'],
        ], $('#campaignErrors'))) return;
        const payload = {
            code: $('#c_code').val(),
            company_id: $('#c_company_id_hidden').val() || '',
            contact_id: $('#c_contact_id_hidden').val() || '',
            service: $('#c_service').val() || '',
            status: $('#c_status').val(),
            deal_value: $('#c_deal_value').val() || 0,
            target_type: $('#c_target_type').val(),
            target_value: $('#c_target_value').val() || 0,
            responsible_user_id: $('#c_responsible_user_id').val() || '',
            budget_approval_date: $('#c_budget_approval_date').val() || '',
            offer_ready_date: $('#c_offer_ready_date').val() || '',
            deadline: $('#c_deadline').val() || '',
            // completion_date is auto-derived + read-only — not submitted.
            next_update_date: $('#c_next_update_date').val() || '',
            _method: 'PUT', _token: csrf
        };
        $.ajax({
            url: "{{ url('campaigns') }}/" + CAMPAIGN_ID, method: 'POST', data: payload, headers: { 'Accept': 'application/json' },
            success: () => location.reload(),
            error: function (xhr) {
                const errs = xhr.responseJSON?.errors ?? {};
                $('#campaignErrors').html(Object.values(errs).flat().join('<br>') || 'An error occurred.').removeClass('hidden');
            }
        });
    });

    /* ── Publication conversations (CRM-style pane) ── */
    // 💬 badges: blue = total messages (updates + replies), red = unread.
    function refreshPubBadges() {
        $.getJSON("{{ route('crm.conversations.counts', 'publication') }}", d => {
            const counts = d.counts || {};
            $('.js-pub-comments').each(function () {
                const id = String($(this).data('id'));
                $(this).find('.conv-count').remove();
                if (counts[id]) {
                    $(this).append('<span class="conv-count ml-0.5 text-[9px] font-bold text-blue-700">' + counts[id] + '</span>');
                }
            });
        });
        $.getJSON("{{ route('notifications.index') }}?unread=1&entityType=publication", d => {
            const map = d.unread || {};
            $('.js-pub-comments').each(function () {
                const id = String($(this).data('id'));
                $(this).find('.notif-bubble').remove();
                if (map[id]) {
                    $(this).append('<span class="notif-bubble ml-0.5 inline-flex items-center justify-center min-w-[16px] h-4 px-1 rounded-full bg-red-500 text-white text-[9px] font-bold">' + map[id] + '</span>');
                }
            });
        });
    }
    refreshPubBadges();
    $(document).on('tuco:conv-closed tuco:conv-opened', refreshPubBadges);

    function openPubThread(id, site) {
        tucoConversations.open({
            type: 'publication',
            id: id,
            label: '{{ $campaign->code }} — ' + (site || '#' + id),
            detailsUrl: "{{ url('storages') }}/" + id + "/edit",
            detailsLabel: 'PUBLICATION DETAILS'
        });
    }

    $(document).on('click', '.js-pub-comments', function () {
        openPubThread($(this).data('id'), $(this).data('site'));
    });

    // Deep link from a notification: ?pubthread=<storageId> auto-opens the
    // pane, then removes the param so refresh doesn't reopen (CRM-style).
    (function () {
        const params = new URLSearchParams(window.location.search);
        const pubId = params.get('pubthread');
        if (!pubId) return;
        params.delete('pubthread');
        history.replaceState({}, '', window.location.pathname + (params.toString() ? '?' + params.toString() : ''));
        const $btn = $('.js-pub-comments[data-id="' + pubId + '"]');
        openPubThread(pubId, $btn.data('site'));
    })();

    @if(session('success'))
    Swal.fire({ icon: 'success', title: '{{ session('success') }}', timer: 1500, showConfirmButton: false });
    @endif
});
</script>
@endpush
