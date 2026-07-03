{{-- resources/views/campaigns/show.blade.php --}}
@extends('layouts.dashboard')
@section('title', $campaign->code)

@php
    $tone         = config('linkbuilding.tone_classes');
    $campTone     = fn($s) => $tone[config('linkbuilding.campaign_status_tones')[$s] ?? 'gray'] ?? 'bg-gray-100 text-gray-700';
    $svcTone      = fn($s) => $tone[config('linkbuilding.service_tones')[$s] ?? 'gray'] ?? 'bg-gray-100 text-gray-700';
    $pubTone      = fn($s) => $tone[config('linkbuilding.publication_status_tones')[$s] ?? 'gray'] ?? 'bg-gray-100 text-gray-700';
    $pubStatusGrp = config('linkbuilding.publication_statuses');
    $fd           = fn($d) => $d ? $d->format('d/m/Y') : '—';
    $prog         = $campaign->progress;
    $g1           = $campaign->publications->where('status_group', 1);
    $g2           = $campaign->publications->where('status_group', 2);
    $published    = $campaign->publications->where('status', 'Published')->count();

    // inline-editable publication cell
    $editable = function ($p, $field, $type, $rawValue, $display) {
        return '<span class="js-pub-edit cursor-pointer rounded px-1 -mx-1 hover:bg-yellow-50 hover:ring-1 hover:ring-yellow-200" '
            . 'data-id="' . $p->id . '" data-field="' . $field . '" data-type="' . $type . '" data-value="' . e((string) ($rawValue ?? '')) . '" title="Click to edit">'
            . $display . '</span>';
    };
@endphp

@section('content')
<div class="px-6 py-6 bg-gray-50 min-h-screen">
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
            <button id="btnNewPub" class="inline-flex items-center gap-1.5 px-3 py-2 text-sm font-semibold text-white bg-green-600 hover:bg-green-700 rounded-lg shadow-sm">
                <x-icon name="plus" size="sm" /> New Publication
            </button>
        </div>
    </div>

    {{-- Stat cards --}}
    <div class="grid grid-cols-2 md:grid-cols-4 lg:grid-cols-7 gap-3 mb-5">
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
        {!! $stat('Offer Ready', $fd($campaign->offer_ready_date)) !!}
        {!! $stat('Deadline', $fd($campaign->deadline)) !!}
        {!! $stat('Next Update', $fd($campaign->next_update_date) . ($campaign->responsibleUser ? '<span class="block text-[10px] text-gray-400 font-normal">'.e($campaign->responsibleUser->name).'</span>' : '')) !!}
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
                <div class="text-xs mt-1">Click “New Publication” to add one.</div>
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
                        <th class="text-left py-2.5 px-3 font-semibold">Notes</th>
                        <th class="py-2.5 px-3 text-right font-semibold">Actions</th>
                    </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @foreach([['GROUP 1 – Site Evaluation', $g1], ['GROUP 2 – Production', $g2]] as [$label, $rows])
                            @if($rows->count())
                                <tr><td colspan="10" class="bg-gray-50/70 text-[10px] font-bold uppercase tracking-wider text-gray-400 px-3 py-1.5">{{ $label }}</td></tr>
                                @foreach($rows as $p)
                                    <tr class="hover:bg-gray-50">
                                        <td class="py-2.5 px-3 font-medium">
                                            <a href="{{ route('crm.publications.show', $p->id) }}" class="text-green-600 hover:underline">{{ $p->site }}</a>
                                        </td>
                                        <td class="py-2.5 px-3">
                                            <button type="button" class="js-pub-status inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-xs font-semibold {{ $pubTone($p->status) }}" data-id="{{ $p->id }}">
                                                {{ $p->status }}
                                                <svg class="w-3 h-3 opacity-60" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7"/></svg>
                                            </button>
                                        </td>
                                        <td class="py-2.5 px-3 text-right whitespace-nowrap">{!! $editable($p, 'price', 'money', (float)$p->price, '€'.number_format((float)$p->price, 0)) !!}</td>
                                        <td class="py-2.5 px-3 text-gray-500 whitespace-nowrap">{!! $editable($p, 'date_to_copywriter', 'date', optional($p->date_to_copywriter)->format('Y-m-d'), $fd($p->date_to_copywriter)) !!}</td>
                                        <td class="py-2.5 px-3 text-gray-500 whitespace-nowrap">{!! $editable($p, 'date_from_copywriter', 'date', optional($p->date_from_copywriter)->format('Y-m-d'), $fd($p->date_from_copywriter)) !!}</td>
                                        <td class="py-2.5 px-3 text-gray-500 whitespace-nowrap">{!! $editable($p, 'date_to_blog', 'date', optional($p->date_to_blog)->format('Y-m-d'), $fd($p->date_to_blog)) !!}</td>
                                        <td class="py-2.5 px-3">{!! $editable($p, 'live_url', 'text', $p->live_url, $p->live_url ? '<span class="text-green-600 text-xs">'.e(\Illuminate\Support\Str::of($p->live_url)->replace(['https://','http://'],'')->limit(24)).'</span>' : '<span class="text-gray-300">—</span>') !!}</td>
                                        <td class="py-2.5 px-3 text-gray-500 whitespace-nowrap">{!! $editable($p, 'live_date', 'date', optional($p->live_date)->format('Y-m-d'), $fd($p->live_date)) !!}</td>
                                        <td class="py-2.5 px-3"><span class="block max-w-[150px] truncate text-gray-500 text-xs" title="{{ $p->notes }}">{{ $p->notes ?: '—' }}</span></td>
                                        <td class="py-2.5 px-3 text-right whitespace-nowrap">
                                            <button type="button" class="js-pub-comments inline-flex items-center justify-center h-7 px-1.5 rounded-md text-gray-400 hover:bg-blue-50 hover:text-blue-600" data-id="{{ $p->id }}" data-site="{{ $p->site }}" title="Comments">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"/></svg>
                                                @if($p->comments_count)<span class="ml-0.5 text-[9px] font-bold text-blue-700">{{ $p->comments_count }}</span>@endif
                                            </button>
                                            <button type="button" class="js-edit-pub inline-flex items-center justify-center w-7 h-7 rounded-md bg-gray-100 text-gray-700 hover:bg-blue-100 hover:text-blue-700" data-id="{{ $p->id }}" title="Edit">
                                                <x-icon name="pencil" size="sm" />
                                            </button>
                                            <button type="button" class="js-del-pub inline-flex items-center justify-center w-7 h-7 rounded-md bg-gray-100 text-gray-700 hover:bg-red-100 hover:text-red-700" data-id="{{ $p->id }}" title="Delete">
                                                <x-icon name="trash" size="sm" />
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

{{-- ═══════════ Publication modal ═══════════ --}}
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
                <input type="text" id="p_site" class="block w-full border border-gray-300 rounded-md text-sm px-3 py-2 focus:ring-green-500 focus:border-green-500" placeholder="e.g. techblog.com">
            </div>
            <div>
                <label class="block text-xs font-semibold text-gray-600 mb-1">Status</label>
                <select id="p_status" class="block w-full border border-gray-300 rounded-md text-sm px-3 py-2 bg-white focus:ring-green-500 focus:border-green-500">
                    @foreach($pubStatusGrp as $group => $statuses)
                        <optgroup label="{{ $group }}">
                            @foreach($statuses as $st)<option value="{{ $st }}">{{ $st }}</option>@endforeach
                        </optgroup>
                    @endforeach
                </select>
            </div>
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-xs font-semibold text-gray-600 mb-1">Price (€)</label>
                    <input type="number" step="0.01" id="p_price" class="block w-full border border-gray-300 rounded-md text-sm px-3 py-2 focus:ring-green-500 focus:border-green-500" placeholder="0">
                </div>
                <div>
                    <label class="block text-xs font-semibold text-gray-600 mb-1">Live Date</label>
                    <input type="text" id="p_live_date" class="js-date block w-full border border-gray-300 rounded-md text-sm px-3 py-2 focus:ring-green-500 focus:border-green-500" placeholder="YYYY-MM-DD">
                </div>
            </div>
            <div>
                <label class="block text-xs font-semibold text-gray-600 mb-1">Live URL</label>
                <input type="text" id="p_live_url" class="block w-full border border-gray-300 rounded-md text-sm px-3 py-2 focus:ring-green-500 focus:border-green-500" placeholder="https://…">
            </div>
            <div class="pt-2 text-[11px] font-bold uppercase tracking-wide text-gray-400 border-t border-gray-100">Production Dates</div>
            <div class="grid grid-cols-3 gap-3">
                <div>
                    <label class="block text-xs font-semibold text-gray-600 mb-1">Sent to Copy</label>
                    <input type="text" id="p_date_to_copywriter" class="js-date block w-full border border-gray-300 rounded-md text-sm px-3 py-2 focus:ring-green-500 focus:border-green-500" placeholder="YYYY-MM-DD">
                </div>
                <div>
                    <label class="block text-xs font-semibold text-gray-600 mb-1">Copy Received</label>
                    <input type="text" id="p_date_from_copywriter" class="js-date block w-full border border-gray-300 rounded-md text-sm px-3 py-2 focus:ring-green-500 focus:border-green-500" placeholder="YYYY-MM-DD">
                </div>
                <div>
                    <label class="block text-xs font-semibold text-gray-600 mb-1">Sent to Blog</label>
                    <input type="text" id="p_date_to_blog" class="js-date block w-full border border-gray-300 rounded-md text-sm px-3 py-2 focus:ring-green-500 focus:border-green-500" placeholder="YYYY-MM-DD">
                </div>
            </div>
            <div>
                <label class="block text-xs font-semibold text-gray-600 mb-1">Notes</label>
                <textarea id="p_notes" rows="2" class="block w-full border border-gray-300 rounded-md text-sm px-3 py-2 focus:ring-green-500 focus:border-green-500"></textarea>
            </div>
        </form>
        <div class="flex justify-end gap-2 px-6 py-4 border-t border-gray-100 sticky bottom-0 bg-white">
            <button type="button" class="js-close-pub px-4 py-2 text-sm font-semibold text-gray-600 bg-white border border-gray-300 rounded-lg hover:bg-gray-50">Cancel</button>
            <button type="button" id="p_save" class="px-4 py-2 text-sm font-semibold text-white bg-green-600 hover:bg-green-700 rounded-lg shadow-sm">Add Publication</button>
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
                <div><label class="block text-xs font-semibold text-gray-600 mb-1">Campaign Code</label><input type="text" id="c_code" class="block w-full border border-gray-300 rounded-md text-sm px-3 py-2 focus:ring-green-500 focus:border-green-500"></div>
                <div>
                    <label class="block text-xs font-semibold text-gray-600 mb-1">Service</label>
                    <select id="c_service" class="block w-full border border-gray-300 rounded-md text-sm px-3 py-2 bg-white focus:ring-green-500 focus:border-green-500">
                        <option value="">—</option>
                        @foreach(config('linkbuilding.services') as $s)<option value="{{ $s }}">{{ $s }}</option>@endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-semibold text-gray-600 mb-1">Status</label>
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
                <div><label class="block text-xs font-semibold text-gray-600 mb-1">Completion</label><input type="text" id="c_completion_date" class="js-date block w-full border border-gray-300 rounded-md text-sm px-3 py-2 focus:ring-green-500 focus:border-green-500"></div>
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

{{-- ═══════════ Publication comments modal ═══════════ --}}
<div id="pubCommentsModal" class="fixed inset-0 z-50 hidden items-center justify-center bg-black/50 p-4">
    <div class="bg-white border border-gray-200 rounded-2xl shadow-2xl w-full max-w-md relative">
        <div class="flex items-center justify-between px-6 py-4 border-b border-gray-100">
            <h2 id="pubCommentsTitle" class="text-lg font-bold text-gray-800">Comments</h2>
            <button type="button" class="js-close-pubcomments text-gray-400 hover:text-gray-600"><x-icon name="x" size="sm" /></button>
        </div>
        <div class="px-6 py-4">
            <div id="pubCommentsList" class="max-h-80 overflow-y-auto mb-4 divide-y divide-gray-100"></div>
            <textarea id="pubCommentBody" rows="3" placeholder="Write a comment…" class="block w-full border border-gray-300 rounded-md text-sm px-3 py-2 focus:ring-green-500 focus:border-green-500"></textarea>
            <div class="flex justify-end mt-3">
                <button type="button" id="pubCommentAdd" class="px-4 py-2 text-sm font-semibold text-white bg-green-600 hover:bg-green-700 rounded-lg shadow-sm">Add Comment</button>
            </div>
        </div>
    </div>
</div>

{{-- floating publication status menu --}}
<div id="pubStatusMenu" class="hidden fixed z-[60] bg-white border border-gray-200 rounded-lg shadow-xl py-1 max-h-72 overflow-y-auto min-w-[240px] text-sm"></div>
@endsection

@push('scripts')
<script>
$(function () {
    const csrf = $('meta[name="csrf-token"]').attr('content');
    const CAMPAIGN_ID = {{ $campaign->id }};
    const PUB_STATUSES = @json($pubStatusGrp);

    // Position a fixed dropdown near a trigger, flipping up if it would overflow the viewport bottom.
    function positionMenu($menu, rect) {
        $menu.removeClass('hidden');
        const mh = $menu.outerHeight(), mw = $menu.outerWidth();
        const top = (rect.bottom + mh > window.innerHeight - 8) ? Math.max(8, rect.top - mh - 4) : rect.bottom + 4;
        const left = Math.max(8, Math.min(rect.left, window.innerWidth - mw - 8));
        $menu.css({ top: top + 'px', left: left + 'px' });
    }

    flatpickr('.js-date', { dateFormat: 'Y-m-d', allowInput: true });

    /* ── New/Edit Publication modal ── */
    const pubModal = $('#pubModal');
    const PUB_DATE_FIELDS = ['live_date', 'date_to_copywriter', 'date_from_copywriter', 'date_to_blog'];
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
        $('#p_site,#p_price,#p_live_url,#p_notes').val('');
        $('#p_status').prop('selectedIndex', 0);
        PUB_DATE_FIELDS.forEach(f => setPubDate(f, ''));
    }

    $('#btnNewPub').on('click', function () {
        resetPub();
        $('#pubModalTitle').text('New Publication');
        $('#p_save').text('Add Publication');
        openPub();
    });

    $('.js-edit-pub').on('click', function () {
        const id = $(this).data('id');
        resetPub();
        $('#pubModalTitle').text('Edit Publication');
        $('#p_save').text('Save Changes');
        $.get("{{ url('publications') }}/" + id + "/edit-ajax", function (res) {
            const d = res.data;
            $('#p_id').val(d.id);
            $('#p_site').val(d.site);
            $('#p_status').val(d.status);
            $('#p_price').val(d.price || '');
            $('#p_live_url').val(d.live_url || '');
            $('#p_notes').val(d.notes || '');
            PUB_DATE_FIELDS.forEach(f => setPubDate(f, d[f]));
            openPub();
        }).fail(() => alert('Unable to load publication.'));
    });

    $('#p_save').on('click', function () {
        $('#pubErrors').addClass('hidden').empty();
        const id = $('#p_id').val();
        const payload = {
            site: $('#p_site').val(),
            status: $('#p_status').val(),
            price: $('#p_price').val() || 0,
            live_url: $('#p_live_url').val() || '',
            live_date: $('#p_live_date').val() || '',
            date_to_copywriter: $('#p_date_to_copywriter').val() || '',
            date_from_copywriter: $('#p_date_from_copywriter').val() || '',
            date_to_blog: $('#p_date_to_blog').val() || '',
            notes: $('#p_notes').val() || '',
            _token: csrf
        };
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

    /* ── Delete publication ── */
    $('.js-del-pub').on('click', function () {
        const id = $(this).data('id');
        Swal.fire({ icon: 'warning', title: 'Delete publication?', showCancelButton: true, confirmButtonText: 'Delete', confirmButtonColor: '#dc2626' })
            .then(r => {
                if (!r.isConfirmed) return;
                $.ajax({ url: "{{ url('publications') }}/" + id, method: 'POST', data: { _method: 'DELETE', _token: csrf }, headers: { 'Accept': 'application/json' },
                    success: () => location.reload() });
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

    /* ── Inline publication status ── */
    const menu = $('#pubStatusMenu');
    let pubTargetId = null;
    (function build() {
        let h = '';
        $.each(PUB_STATUSES, function (group, list) {
            h += '<div class="px-3 pt-2 pb-1 text-[9px] uppercase tracking-wider text-gray-400 font-bold">' + group + '</div>';
            list.forEach(s => { h += '<div class="js-pub-opt px-3 py-1.5 hover:bg-gray-50 cursor-pointer" data-status="' + s.replace(/"/g, '&quot;') + '">' + s + '</div>'; });
        });
        menu.html(h);
    })();
    $('.js-pub-status').on('click', function (e) {
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
            completion_date: $('#c_completion_date').val() || '',
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

    /* ── Publication comments modal ── */
    const pubCommentsModal = $('#pubCommentsModal');
    let pubCommentsId = null;
    function closePubComments() { pubCommentsModal.addClass('hidden').removeClass('flex'); }
    $('.js-close-pubcomments').on('click', closePubComments);
    pubCommentsModal.on('click', e => { if (e.target === pubCommentsModal[0]) closePubComments(); });
    function renderPubComments(list) {
        if (!list.length) { $('#pubCommentsList').html('<p class="text-sm text-gray-400 py-2">No comments yet.</p>'); return; }
        $('#pubCommentsList').html(list.map(c =>
            '<div class="py-2.5"><div class="text-[10px] text-gray-400 mb-0.5"><strong class="text-gray-600">' + $('<i/>').text(c.author).html() + '</strong> · ' + (c.date || '') + '</div><div class="text-sm text-gray-800">' + $('<i/>').text(c.body).html() + '</div></div>'
        ).join(''));
    }
    $(document).on('click', '.js-pub-comments', function () {
        pubCommentsId = $(this).data('id');
        $('#pubCommentsTitle').text('Comments — ' + $(this).data('site'));
        $('#pubCommentBody').val('');
        $('#pubCommentsList').html('<p class="text-sm text-gray-400 py-2">Loading…</p>');
        pubCommentsModal.removeClass('hidden').addClass('flex');
        $.get("{{ url('publications') }}/" + pubCommentsId + "/comments", res => renderPubComments(res.data || []));
    });
    $('#pubCommentAdd').on('click', function () {
        const body = $('#pubCommentBody').val().trim();
        if (!body) return;
        $.ajax({ url: "{{ url('publications') }}/" + pubCommentsId + "/comments", method: 'POST', data: { body, _token: csrf }, headers: { 'Accept': 'application/json' },
            success: function () { $('#pubCommentBody').val(''); $.get("{{ url('publications') }}/" + pubCommentsId + "/comments", res => renderPubComments(res.data || [])); } });
    });

    @if(session('success'))
    Swal.fire({ icon: 'success', title: '{{ session('success') }}', timer: 1500, showConfirmButton: false });
    @endif
});
</script>
@endpush
