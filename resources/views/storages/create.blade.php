{{-- resources/views/storages/create.blade.php --}}
@extends('layouts.dashboard')

@section('content')
    <div class="px-6 py-4 bg-gray-50 min-h-screen text-xs">
        <!-- Header -->
        <div class="flex flex-col gap-3 mb-4">
            <h1 class="text-lg font-bold text-gray-700">Create Storage</h1>
        </div>

        <form method="POST" action="{{ route('storages.store') }}" class="space-y-4 text-xs">
            @csrf

            {{-- ───────────── GENERAL / FK SECTION ───────────── --}}
            <div class="grid grid-cols-2 gap-4">

                {{-- Website --}}
                <div>
                    <label class="block text-gray-700 font-medium mb-1">Website</label>
                    <select name="website_id" id="websiteSelect"
                            class="w-full border border-gray-300 rounded px-2 py-1
                   focus:ring-cyan-500 focus:border-cyan-500">
                        <option value="">-- None --</option>
                        @foreach($websites as $w)
                            <option value="{{ $w->id }}"
                                {{ old('website_id')==$w->id ? 'selected' : '' }}>
                                {{ $w->domain_name }}
                            </option>
                        @endforeach
                    </select>
                    @error('website_id')
                    <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                    @enderror
                </div>
                {{-- Status --}}
                <div>
                    <label class="block text-gray-700 font-medium mb-1">Status</label>
                    <select name="status"
                            class="w-full border border-gray-300 rounded px-2 py-1 focus:ring-cyan-500 focus:border-cyan-500">
                        <option value="">-- None --</option>
                        <option value="article_published"      {{ old('status')=='article_published'      ? 'selected' : '' }}>Article Published</option>
                        <option value="requirements_not_met"    {{ old('status')=='requirements_not_met'   ? 'selected' : '' }}>Requirements not met</option>
                        <option value="already_used_by_client"  {{ old('status')=='already_used_by_client' ? 'selected' : '' }}>Already used by client</option>
                        <option value="out_of_topic"            {{ old('status')=='out_of_topic'           ? 'selected' : '' }}>Out of topic</option>
                        <option value="high_price"              {{ old('status')=='high_price'             ? 'selected' : '' }}>High Price</option>
                    </select>
                    @error('status') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                </div>

                {{-- LB --}}
                <div>
                    <label class="block text-gray-700 font-medium mb-1">LB</label>
                    <input type="text" name="LB" value="{{ old('LB') }}"
                           class="w-full border border-gray-300 rounded px-2 py-1 focus:ring-cyan-500 focus:border-cyan-500">
                    @error('LB') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                </div>

                {{-- Client --}}
                <div>
                    <label class="block text-gray-700 font-medium mb-1">Client</label>
                    <select name="client_id" id="clientSelect"
                            class="w-full border border-gray-300 rounded px-2 py-1 focus:ring-cyan-500 focus:border-cyan-500">
                        <option value="">-- None --</option>
                        @foreach($clients as $cl)
                            <option value="{{ $cl->id }}" {{ old('client_id')==$cl->id ? 'selected' : '' }}>
                                {{ $cl->first_name }} {{ $cl->last_name }}
                            </option>
                        @endforeach
                    </select>
                    @error('client_id') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                </div>

                {{-- Copywriter --}}
                <div>
                    <label class="block text-gray-700 font-medium mb-1">Copywriter</label>
                    <select name="copy_id" id="copywriterSelect"
                            class="w-full border border-gray-300 rounded px-2 py-1 focus:ring-cyan-500 focus:border-cyan-500">
                        <option value="">-- None --</option>
                        @foreach($copies as $cp)
                            <option value="{{ $cp->id }}" {{ old('copy_id')==$cp->id ? 'selected' : '' }}>
                                {{ $cp->copy_val }}
                            </option>
                        @endforeach
                    </select>
                    @error('copy_id') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                </div>

                {{-- Country --}}
                <div>
                    <label class="block text-gray-700 font-medium mb-1">Country</label>
                    <select name="country_id"
                            class="w-full border border-gray-300 rounded px-2 py-1 focus:ring-cyan-500 focus:border-cyan-500">
                        <option value="">-- None --</option>
                        @foreach($countries as $c)
                            <option value="{{ $c->id }}" {{ old('country_id')==$c->id ? 'selected' : '' }}>
                                {{ $c->country_name }}
                            </option>
                        @endforeach
                    </select>
                    @error('country_id') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                </div>

                {{-- Language --}}
                <div>
                    <label class="block text-gray-700 font-medium mb-1">Language</label>
                    <select name="language_id"
                            class="w-full border border-gray-300 rounded px-2 py-1 focus:ring-cyan-500 focus:border-cyan-500">
                        <option value="">-- None --</option>
                        @foreach($languages as $l)
                            <option value="{{ $l->id }}" {{ old('language_id')==$l->id ? 'selected' : '' }}>
                                {{ $l->name }}
                            </option>
                        @endforeach
                    </select>
                    @error('language_id') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                </div>
            </div>

            {{-- ───────────── COPY-DETAILS ───────────── --}}
            <div class="grid grid-cols-4 gap-4">
{{--                <div>--}}
{{--                    <label class="block text-gray-700 font-medium mb-1">Copywriter Amount EUR €</label>--}}
{{--                    <input type="number" name="copy_nr" value="{{ old('copy_nr') }}"--}}
{{--                           class="w-full border border-gray-300 rounded px-2 py-1 focus:ring-cyan-500 focus:border-cyan-500">--}}
{{--                    @error('copy_nr') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror--}}
{{--                </div>--}}
                <div>
                    <label class="block text-gray-700 font-medium mb-1">Copy Comm. Date</label>
                    <input type="text" name="copywriter_commision_date" value="{{ old('copywriter_commision_date') }}"
                           class="date-input w-full border border-gray-300 rounded px-2 py-1 focus:ring-cyan-500 focus:border-cyan-500">
                    @error('copywriter_commision_date')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label class="block text-gray-700 font-medium mb-1">Copy Subm. Date</label>
                    <input type="text" name="copywriter_submission_date" value="{{ old('copywriter_submission_date') }}"
                           class="date-input w-full border border-gray-300 rounded px-2 py-1 focus:ring-cyan-500 focus:border-cyan-500">
                    @error('copywriter_submission_date')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
                </div>
            </div>

            <div class="grid grid-cols-4 gap-4">
                {{-- Currency --}}
                <div>
                    <label class="block text-gray-700 font-medium mb-1">Publisher Currency</label>
                    <select name="publisher_currency"
                            class="w-full border border-gray-300 rounded px-2 py-1 focus:ring-cyan-500 focus:border-cyan-500">
                        <option value="EUR" {{ old('publisher_currency','EUR')=='EUR' ? 'selected' : '' }}>EUR</option>
                        <option value="USD" {{ old('publisher_currency')=='USD' ? 'selected' : '' }}>USD</option>
                    </select>
                    @error('publisher_currency') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                </div>

                {{-- Amount --}}
                <div>
                    <label class="block text-gray-700 font-medium mb-1">Publisher Amount</label>
                    <input type="number" step="0.01" name="publisher_amount" value="{{ old('publisher_amount') }}"
                           class="w-full border border-gray-300 rounded px-2 py-1 focus:ring-cyan-500 focus:border-cyan-500">
                    @error('publisher_amount') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                </div>
            </div>
            {{-- ───────────── PRICES & COSTS (auto-calculated fields are NOT shown) ───────────── --}}
            <div class="grid grid-cols-4 gap-4">
                @foreach([
                    'publisher'         => 'Publisher Agreed Amount €',
                    'copy_nr' => 'Copywriter Amount €',
                    'menford'           => 'Menford €',
                    'client_copy'       => 'Client Copy €',
                ] as $field => $label)
                    <div>
                        <label class="block text-gray-700 font-medium mb-1">{{ $label }}</label>
                        <input type="number" step="0.01" name="{{ $field }}" value="{{ old($field) }}"
                               class="w-full border border-gray-300 rounded px-2 py-1 focus:ring-cyan-500 focus:border-cyan-500">
                        @error($field) <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                    </div>
                @endforeach
            </div>

            {{-- ───────────── CAMPAIGN & LINK FIELDS ───────────── --}}
            <div class="grid grid-cols-3 gap-4">
                <div>
                    <label class="block text-gray-700 font-medium mb-1">Target Domain</label>
                    <input type="text" name="campaign" value="{{ old('campaign') }}"
                           class="w-full border border-gray-300 rounded px-2 py-1 focus:ring-cyan-500 focus:border-cyan-500">
                    @error('campaign') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="block text-gray-700 font-medium mb-1">Anchor Text</label>
                    <input type="text" name="anchor_text" value="{{ old('anchor_text') }}"
                           class="w-full border border-gray-300 rounded px-2 py-1 focus:ring-cyan-500 focus:border-cyan-500">
                    @error('anchor_text') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="block text-gray-700 font-medium mb-1">Target URL</label>
                    <input type="url" name="target_url" value="{{ old('target_url') }}"
                           class="w-full border border-gray-300 rounded px-2 py-1 focus:ring-cyan-500 focus:border-cyan-500">
                    @error('target_url') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="block text-gray-700 font-medium mb-1">Campaign Code</label>
                    <input type="text" name="campaign_code" value="{{ old('campaign_code') }}"
                           class="w-full border border-gray-300 rounded px-2 py-1 focus:ring-cyan-500 focus:border-cyan-500">
                    @error('campaign_code') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                </div>
            </div>

            {{-- ───────────── PUBLICATION DATES / PERIODS ───────────── --}}
            <div class="grid grid-cols-4 gap-4">
                <div>
                    <label class="block text-gray-700 font-medium mb-1">Sent to Publisher</label>
                    <input type="text" name="article_sent_to_publisher" value="{{ old('article_sent_to_publisher') }}"
                           class="date-input w-full border border-gray-300 rounded px-2 py-1 focus:ring-cyan-500 focus:border-cyan-500">
                    @error('article_sent_to_publisher')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label class="block text-gray-700 font-medium mb-1">Publication Date</label>
                    <input type="text" name="publication_date" value="{{ old('publication_date') }}"
                           class="date-input w-full border border-gray-300 rounded px-2 py-1 focus:ring-cyan-500 focus:border-cyan-500">
                    @error('publication_date')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label class="block text-gray-700 font-medium mb-1">Expiration Date</label>
                    <input type="text" name="expiration_date" value="{{ old('expiration_date') }}"
                           class="date-input w-full border border-gray-300 rounded px-2 py-1 focus:ring-cyan-500 focus:border-cyan-500">
                    @error('expiration_date')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
                </div>

                <div class="col-span-2">
                    <label class="block text-gray-700 font-medium mb-1">Article URL</label>
                    <input type="url" name="article_url" value="{{ old('article_url') }}"
                           class="w-full border border-gray-300 rounded px-2 py-1 focus:ring-cyan-500 focus:border-cyan-500">
                    @error('article_url') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                </div>
            </div>

            {{-- ───────────── INVOICING & PAYMENTS ───────────── --}}
            <div class="grid grid-cols-3 gap-4">
                <div>
                    <label class="block text-gray-700 font-medium mb-1">Pay to Us Method</label>
                    <input type="text" name="method_payment_to_us" value="{{ old('method_payment_to_us') }}"
                           class="w-full border border-gray-300 rounded px-2 py-1 focus:ring-cyan-500 focus:border-cyan-500">
                    @error('method_payment_to_us')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label class="block text-gray-700 font-medium mb-1">Invoice Menford Date</label>
                    <input type="text" name="invoice_menford" value="{{ old('invoice_menford') }}"
                           class="date-input w-full border border-gray-300 rounded px-2 py-1 focus:ring-cyan-500 focus:border-cyan-500">
                    @error('invoice_menford')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label class="block text-gray-700 font-medium mb-1">Invoice Menford Nr</label>
                    <input type="text" name="invoice_menford_nr" value="{{ old('invoice_menford_nr') }}"
                           class="w-full border border-gray-300 rounded px-2 py-1 focus:ring-cyan-500 focus:border-cyan-500">
                    @error('invoice_menford_nr')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label class="block text-gray-700 font-medium mb-1">Invoice Company</label>
                    <input type="text" name="invoice_company" value="{{ old('invoice_company') }}"
                           class="w-full border border-gray-300 rounded px-2 py-1 focus:ring-cyan-500 focus:border-cyan-500">
                    @error('invoice_company')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label class="block text-gray-700 font-medium mb-1">Pay to Us Date</label>
                    <input type="text" name="payment_to_us_date" value="{{ old('payment_to_us_date') }}"
                           class="date-input w-full border border-gray-300 rounded px-2 py-1 focus:ring-cyan-500 focus:border-cyan-500">
                    @error('payment_to_us_date')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
                </div>
            </div>

            <div class="grid grid-cols-3 gap-4">
                <div>
                    <label class="block text-gray-700 font-medium mb-1">Bill Publisher Name</label>
                    <input type="text" name="bill_publisher_name" value="{{ old('bill_publisher_name') }}"
                           class="w-full border border-gray-300 rounded px-2 py-1 focus:ring-cyan-500 focus:border-cyan-500">
                    @error('bill_publisher_name')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label class="block text-gray-700 font-medium mb-1">Bill Publisher Nr</label>
                    <input type="text" name="bill_publisher_nr" value="{{ old('bill_publisher_nr') }}"
                           class="w-full border border-gray-300 rounded px-2 py-1 focus:ring-cyan-500 focus:border-cyan-500">
                    @error('bill_publisher_nr')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label class="block text-gray-700 font-medium mb-1">Bill Publisher Date</label>
                    <input type="text" name="bill_publisher_date" value="{{ old('bill_publisher_date') }}"
                           class="date-input w-full border border-gray-300 rounded px-2 py-1 focus:ring-cyan-500 focus:border-cyan-500">
                    @error('bill_publisher_date')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label class="block text-gray-700 font-medium mb-1">Pay to Publisher Date</label>
                    <input type="text" name="payment_to_publisher_date" value="{{ old('payment_to_publisher_date') }}"
                           class="date-input w-full border border-gray-300 rounded px-2 py-1 focus:ring-cyan-500 focus:border-cyan-500">
                    @error('payment_to_publisher_date')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label class="block text-gray-700 font-medium mb-1">Pay to Publisher Method</label>
                    <input type="text" name="method_payment_to_publisher" value="{{ old('method_payment_to_publisher') }}"
                           class="w-full border border-gray-300 rounded px-2 py-1 focus:ring-cyan-500 focus:border-cyan-500">
                    @error('method_payment_to_publisher')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
                </div>
            </div>

            {{-- ───────────── FILES & EXTRA NOTES ───────────── --}}
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-gray-700 font-medium mb-1">Files (path or URL)</label>
                    <input type="text" name="files" value="{{ old('files') }}"
                           class="w-full border border-gray-300 rounded px-2 py-1 focus:ring-cyan-500 focus:border-cyan-500">
                    @error('files') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="block text-gray-700 font-medium mb-1">Extra Notes</label>
                    <textarea name="extra_notes" rows="3"
                              class="w-full border border-gray-300 rounded px-2 py-1 focus:ring-cyan-500 focus:border-cyan-500">{{ old('extra_notes') }}</textarea>
                    @error('extra_notes') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                </div>
            </div>

            {{-- ───────────── CATEGORIES ───────────── --}}
            <div>
                <label class="block text-gray-700 font-medium mb-1">Categories</label>
                <select name="category_ids[]" id="categorySelect" multiple
                        class="w-full border border-gray-300 rounded px-2 py-1 focus:ring-cyan-500 focus:border-cyan-500">
                    @foreach($categories as $cat)
                        <option value="{{ $cat->id }}"
                            {{ collect(old('category_ids'))->contains($cat->id) ? 'selected' : '' }}>
                            {{ $cat->name }}
                        </option>
                    @endforeach
                </select>
            </div>

            {{-- ───────────── SUBMIT BUTTON ───────────── --}}
            <button type="submit"
                    class="bg-cyan-600 text-white px-16 py-2 rounded shadow hover:bg-cyan-700
                       focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-cyan-500 text-lg">
                Save
            </button>
        </form>
    </div>
@endsection

@push('scripts')
    <script>
        $(document).ready(function() {

            $('#categorySelect, #clientSelect, #copywriterSelect, #websiteSelect').select2({
                placeholder:'Select',
                closeOnSelect:false,
                width:'resolve',
                dropdownAutoWidth:true,
                containerCssClass:'text-xs',
                dropdownCssClass:'text-xs'
            });

            flatpickr('.date-input', {
                dateFormat: 'd/m/Y',   // what the user sees *and* what is sent to PHP
                allowInput: true
            });

        });


    </script>
@endpush
