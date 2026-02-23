@extends('layouts.dashboard')

@section('content')
    <div class="px-6 py-4 bg-gray-50 min-h-screen text-xs">
        <!-- Header: Title + Buttons -->
        <div class="flex flex-col gap-3 mb-4">
    <h1 class="text-lg font-bold text-gray-700 mb-4">Create Domain</h1>

    <form method="POST" action="{{ route('websites.store') }}" class="space-y-4 text-xs">
        @csrf

        <!-- General Information -->
        <div class="grid grid-cols-2 gap-4">
            <div>
                <label class="block text-gray-700 font-medium mb-1">Domain Name</label>
                <input
                    type="text"
                    name="domain_name"
                    value="{{ old('domain_name') }}"
                    class="w-full border border-gray-300 rounded px-2 py-1 focus:ring-cyan-500 focus:border-cyan-500"
                    required
                >
                @error('domain_name')
                <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                @enderror
            </div>
            <div>
                <label class="block text-gray-700 font-medium mb-1">Status</label>
                <select
                    name="status"
                    class="w-full border border-gray-300 rounded px-2 py-1 focus:ring-cyan-500 focus:border-cyan-500"
                >
                    <option value="">-- None --</option>
                    <option value="active" {{ old('status') === 'active' ? 'selected' : '' }}>Active</option>
                    <option value="past" {{ old('status') === 'past' ? 'selected' : '' }}>Past</option>
                </select>
                @error('status')
                <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                @enderror
            </div>
        </div>

        <!-- Additional General Fields -->
        <div class="grid grid-cols-2 gap-4">
            <div>
                <label class="block text-gray-700 font-medium mb-1">Type of Domain</label>
                <select
                    name="type_of_website"
                    class="w-full border border-gray-300 rounded px-2 py-1 focus:ring-cyan-500 focus:border-cyan-500"
                >
                    <option value="">-- None --</option>
                    <option value="VERTICAL" {{ old('type_of_website') === 'VERTICAL' ? 'selected' : '' }}>Vertical</option>
                    <option value="GENERALIST" {{ old('type_of_website') === 'GENERALIST' ? 'selected' : '' }}>Generalist</option>
                    <option value="LOCAL" {{ old('type_of_website') === 'LOCAL' ? 'selected' : '' }}>Local</option>
                </select>
                @error('type_of_website')
                <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                @enderror
            </div>
            <div>
                <label class="block text-gray-700 font-medium mb-1">Linkbuilder</label>
                <input
                    type="text"
                    name="linkbuilder"
                    value="{{ old('linkbuilder') }}"
                    class="w-full border border-gray-300 rounded px-2 py-1 focus:ring-cyan-500 focus:border-cyan-500"
                >
                @error('linkbuilder')
                <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                @enderror
            </div>
        </div>

        <!-- Foreign Keys -->
        <div class="grid grid-cols-2 gap-4">
            <div>
                <label class="block text-gray-700 font-medium mb-1">Country</label>
                <select
                    name="country_id"
                    class="w-full border border-gray-300 rounded px-2 py-1 focus:ring-cyan-500 focus:border-cyan-500"
                >
                    <option value="">-- None --</option>
                    @foreach($countries as $c)
                        <option value="{{ $c->id }}" {{ old('country_id') == $c->id ? 'selected' : '' }}>
                            {{ $c->country_name }}
                        </option>
                    @endforeach
                </select>
                @error('country_id')
                <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                @enderror
            </div>
            <div>
                <label class="block text-gray-700 font-medium mb-1">Language</label>
                <select
                    name="language_id"
                    class="w-full border border-gray-300 rounded px-2 py-1 focus:ring-cyan-500 focus:border-cyan-500"
                >
                    <option value="">-- None --</option>
                    @foreach($languages as $lang)
                        <option value="{{ $lang->id }}" {{ old('language_id') == $lang->id ? 'selected' : '' }}>
                            {{ $lang->name }}
                        </option>
                    @endforeach
                </select>
                @error('language_id')
                <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                @enderror
            </div>
        </div>

        <div class="grid grid-cols-2 gap-4">
            <div>
                <label class="block text-gray-700 font-medium mb-1">Publisher</label>
                <select
                    name="contact_id"
                    class="w-full border border-gray-300 rounded px-2 py-1 focus:ring-cyan-500 focus:border-cyan-500"
                >
                    <option value="">-- None --</option>
                    @foreach($contacts as $cnt)
                        <option value="{{ $cnt->id }}" {{ old('contact_id') == $cnt->id ? 'selected' : '' }}>
                            {{ $cnt->name }}
                        </option>
                    @endforeach
                </select>
                @error('contact_id')
                <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                @enderror
            </div>
            <div>
                <label class="block text-gray-700 font-medium mb-1">Currency</label>
                <select
                    name="currency_code"
                    class="w-full border border-gray-300 rounded px-2 py-1 focus:ring-cyan-500 focus:border-cyan-500"
                >
                    <option value="">-- None --</option>
                    <option value="EUR" {{ old('currency_code') == 'EUR' ? 'selected' : '' }}>EUR</option>
                    <option value="USD" {{ old('currency_code') == 'USD' ? 'selected' : '' }}>USD</option>
                </select>
                @error('currency_code')
                <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                @enderror
            </div>
        </div>

        <!-- Price Details & Dates -->
        <div class="grid grid-cols-3 gap-4">
            <div>
                <label class="block text-gray-700 font-medium mb-1">Publisher Price</label>
                <input
                    type="number"
                    step="0.01"
                    name="publisher_price"
                    value="{{ old('publisher_price') }}"
                    class="w-full border border-gray-300 rounded px-2 py-1 focus:ring-cyan-500 focus:border-cyan-500"
                >
                @error('publisher_price')
                <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                @enderror
            </div>
            <div>
                <label class="block text-gray-700 font-medium mb-1">Original Publisher Price</label>
                <input
                    type="number"
                    step="0.01"
                    name="original_publisher_price"
                    value="{{ old('original_publisher_price') }}"
                    class="w-full border border-gray-300 rounded px-2 py-1 focus:ring-cyan-500 focus:border-cyan-500"
                >
                @error('original_publisher_price')
                <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                @enderror
            </div>
            <div>
                <label class="block text-gray-700 font-medium mb-1">Date Publisher Price</label>
                <input
                    type="text"
                    name="date_publisher_price"
                    value="{{ old('date_publisher_price') }}"
                    class="date-input w-full border border-gray-300 rounded px-2 py-1 focus:ring-cyan-500 focus:border-cyan-500"
                >
                @error('date_publisher_price')
                <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                @enderror
            </div>
            <div>
                <label class="block text-gray-700 font-medium mb-1">Link Insertion Price</label>
                <input
                    type="number"
                    step="0.01"
                    name="link_insertion_price"
                    value="{{ old('link_insertion_price') }}"
                    class="w-full border border-gray-300 rounded px-2 py-1 focus:ring-cyan-500 focus:border-cyan-500"
                >
                @error('link_insertion_price')
                <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                @enderror
            </div>
            <div>
                <label class="block text-gray-700 font-medium mb-1">Original Link Insertion Price</label>
                <input
                    type="number"
                    step="0.01"
                    name="original_link_insertion_price"
                    value="{{ old('original_link_insertion_price') }}"
                    class="w-full border border-gray-300 rounded px-2 py-1 focus:ring-cyan-500 focus:border-cyan-500"
                >
                @error('original_link_insertion_price')
                <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                @enderror
            </div>
        </div>

        <div class="grid grid-cols-3 gap-4">
            <div>
                <label class="block text-gray-700 font-medium mb-1">No Follow Price</label>
                <input
                    type="number"
                    step="0.01"
                    name="no_follow_price"
                    value="{{ old('no_follow_price') }}"
                    class="w-full border border-gray-300 rounded px-2 py-1 focus:ring-cyan-500 focus:border-cyan-500"
                >
                @error('no_follow_price')
                <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                @enderror
            </div>
            <div>
                <label class="block text-gray-700 font-medium mb-1">Original No Follow Price</label>
                <input
                    type="number"
                    step="0.01"
                    name="original_no_follow_price"
                    value="{{ old('original_no_follow_price') }}"
                    class="w-full border border-gray-300 rounded px-2 py-1 focus:ring-cyan-500 focus:border-cyan-500"
                >
                @error('original_no_follow_price')
                <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                @enderror
            </div>
            <div>
                <label class="block text-gray-700 font-medium mb-1">Special Topic Price</label>
                <input
                    type="number"
                    step="0.01"
                    name="special_topic_price"
                    value="{{ old('special_topic_price') }}"
                    class="w-full border border-gray-300 rounded px-2 py-1 focus:ring-cyan-500 focus:border-cyan-500"
                >
                @error('special_topic_price')
                <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                @enderror
            </div>
            <div>
                <label class="block text-gray-700 font-medium mb-1">Sensitive Topic Price</label>
                <input
                    type="number"
                    step="0.01"
                    name="sensitive_topic_price"
                    value="{{ old('sensitive_topic_price') }}"
                    class="w-full border border-gray-300 rounded px-2 py-1 focus:ring-cyan-500 focus:border-cyan-500"
                >
                @error('sensitive_topic_price')
                <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                @enderror
            </div>
            <div>
                <label class="block text-gray-700 font-medium mb-1">Original Special Topic Price</label>
                <input
                    type="number"
                    step="0.01"
                    name="original_special_topic_price"
                    value="{{ old('original_special_topic_price') }}"
                    class="w-full border border-gray-300 rounded px-2 py-1 focus:ring-cyan-500 focus:border-cyan-500"
                >
                @error('original_special_topic_price')
                <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                @enderror
            </div>
        </div>

        {{-- Banner & Sitewide Link --}}
        <div class="grid grid-cols-3 gap-4 mt-4">
            {{-- Banner --}}
            <div>
                <label class="block font-medium mb-1">Banner Price</label>
                <input type="number" step="0.01" name="banner_price"
                       value="{{ old('banner_price', $website->banner_price ?? '') }}"
                       class="w-full border border-gray-300 rounded px-2 py-1 focus:ring-cyan-500">
                @error('banner_price') <p class="text-red-500 text-xs">{{ $message }}</p> @enderror
            </div>
            <div>
                <label class="block font-medium mb-1">Original Banner Price</label>
                <input type="number" step="0.01" name="original_banner_price"
                       value="{{ old('original_banner_price', $website->original_banner_price ?? '') }}"
                       class="w-full border border-gray-300 rounded px-2 py-1 focus:ring-cyan-500">
                @error('original_banner_price') <p class="text-red-500 text-xs">{{ $message }}</p> @enderror
            </div>

            {{-- Site-wide link --}}
            <div>
                <label class="block font-medium mb-1">Site-wide Link Price</label>
                <input type="number" step="0.01" name="sitewide_link_price"
                       value="{{ old('sitewide_link_price', $website->sitewide_link_price ?? '') }}"
                       class="w-full border border-gray-300 rounded px-2 py-1 focus:ring-cyan-500">
                @error('sitewide_link_price') <p class="text-red-500 text-xs">{{ $message }}</p> @enderror
            </div>
            <div>
                <label class="block font-medium mb-1">Original Site-wide Link Price</label>
                <input type="number" step="0.01" name="original_sitewide_link_price"
                       value="{{ old('original_sitewide_link_price', $website->original_sitewide_link_price ?? '') }}"
                       class="w-full border border-gray-300 rounded px-2 py-1 focus:ring-cyan-500">
                @error('original_sitewide_link_price') <p class="text-red-500 text-xs">{{ $message }}</p> @enderror
            </div>
        </div>

        <!-- Evaluations -->
        <div class="grid grid-cols-2 gap-4">
            <div>
                <label class="block text-gray-700 font-medium mb-1">Kialvo Evaluation</label>
                <input
                    type="number"
                    step="0.01"
                    name="kialvo_evaluation"
                    value="{{ old('kialvo_evaluation') }}"
                    class="w-full border border-gray-300 rounded px-2 py-1 focus:ring-cyan-500 focus:border-cyan-500"
                >
                @error('kialvo_evaluation')
                <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                @enderror
            </div>
            <div>
                <label class="block text-gray-700 font-medium mb-1">Date Kialvo Evaluation</label>
                <input
                    type="text"
                    name="date_kialvo_evaluation"
                    value="{{ old('date_kialvo_evaluation') }}"
                    class="date-input w-full border border-gray-300 rounded px-2 py-1 focus:ring-cyan-500 focus:border-cyan-500"
                >
                @error('date_kialvo_evaluation')
                <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                @enderror
            </div>
        </div>

        <!-- SEO Metrics -->
        <div class="grid grid-cols-4 gap-4">
            <div>
                <label class="block text-gray-700 font-medium mb-1">DA</label>
                <input
                    type="number"
                    name="DA"
                    value="{{ old('DA') }}"
                    class="w-full border border-gray-300 rounded px-2 py-1 focus:ring-cyan-500 focus:border-cyan-500"
                >
                @error('DA')
                <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                @enderror
            </div>
            <div>
                <label class="block text-gray-700 font-medium mb-1">PA</label>
                <input
                    type="number"
                    name="PA"
                    value="{{ old('PA') }}"
                    class="w-full border border-gray-300 rounded px-2 py-1 focus:ring-cyan-500 focus:border-cyan-500"
                >
                @error('PA')
                <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                @enderror
            </div>
            <div>
                <label class="block text-gray-700 font-medium mb-1">TF</label>
                <input
                    type="number"
                    name="TF"
                    value="{{ old('TF') }}"
                    class="w-full border border-gray-300 rounded px-2 py-1 focus:ring-cyan-500 focus:border-cyan-500"
                >
                @error('TF')
                <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                @enderror
            </div>
            <div>
                <label class="block text-gray-700 font-medium mb-1">CF</label>
                <input
                    type="number"
                    name="CF"
                    value="{{ old('CF') }}"
                    class="w-full border border-gray-300 rounded px-2 py-1 focus:ring-cyan-500 focus:border-cyan-500"
                >
                @error('CF')
                <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                @enderror
            </div>
        </div>

        <div class="grid grid-cols-4 gap-4">
            <div>
                <label class="block text-gray-700 font-medium mb-1">DR</label>
                <input
                    type="number"
                    name="DR"
                    value="{{ old('DR') }}"
                    class="w-full border border-gray-300 rounded px-2 py-1 focus:ring-cyan-500 focus:border-cyan-500"
                >
                @error('DR')
                <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                @enderror
            </div>
            <div>
                <label class="block text-gray-700 font-medium mb-1">UR</label>
                <input
                    type="number"
                    name="UR"
                    value="{{ old('UR') }}"
                    class="w-full border border-gray-300 rounded px-2 py-1 focus:ring-cyan-500 focus:border-cyan-500"
                >
                @error('UR')
                <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                @enderror
            </div>
            <div>
                <label class="block text-gray-700 font-medium mb-1">ZA</label>
                <input
                    type="number"
                    name="ZA"
                    value="{{ old('ZA') }}"
                    class="w-full border border-gray-300 rounded px-2 py-1 focus:ring-cyan-500 focus:border-cyan-500"
                >
                @error('ZA')
                <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                @enderror
            </div>
            <div>
                <label class="block text-gray-700 font-medium mb-1">AS (Metric)</label>
                <input
                    type="number"
                    name="as_metric"
                    value="{{ old('as_metric') }}"
                    class="w-full border border-gray-300 rounded px-2 py-1 focus:ring-cyan-500 focus:border-cyan-500"
                >
                @error('as_metric')
                <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                @enderror
            </div>
        </div>

        <div class="grid grid-cols-4 gap-4">
            <div>
                <label class="block text-gray-700 font-medium mb-1">SEO Zoom</label>
                <input
                    type="text"
                    name="seozoom"
                    value="{{ old('seozoom') }}"
                    class="w-full border border-gray-300 rounded px-2 py-1 focus:ring-cyan-500 focus:border-cyan-500"
                >
                @error('seozoom')
                <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                @enderror
            </div>
            <div>
                <label class="block text-gray-700 font-medium mb-1">Semrush Traffic</label>
                <input
                    type="number"
                    name="semrush_traffic"
                    value="{{ old('semrush_traffic') }}"
                    class="w-full border border-gray-300 rounded px-2 py-1 focus:ring-cyan-500 focus:border-cyan-500"
                >
                @error('semrush_traffic')
                <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                @enderror
            </div>
            <div>
                <label class="block text-gray-700 font-medium mb-1">Ahrefs Keyword</label>
                <input
                    type="number"
                    name="ahrefs_keyword"
                    value="{{ old('ahrefs_keyword') }}"
                    class="w-full border border-gray-300 rounded px-2 py-1 focus:ring-cyan-500 focus:border-cyan-500"
                >
                @error('ahrefs_keyword')
                <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                @enderror
            </div>
        </div>

        <div class="grid grid-cols-4 gap-4">
            <div>
                <label class="block text-gray-700 font-medium mb-1">Ahrefs Traffic</label>
                <input
                    type="number"
                    name="ahrefs_traffic"
                    value="{{ old('ahrefs_traffic') }}"
                    class="w-full border border-gray-300 rounded px-2 py-1 focus:ring-cyan-500 focus:border-cyan-500"
                >
                @error('ahrefs_traffic')
                <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                @enderror
            </div>
            <div>
                <label class="block text-gray-700 font-medium mb-1">SEO Metrics Date</label>
                <input
                    type="text"

                    name="seo_metrics_date"
                    value="{{ old('seo_metrics_date') }}"
                    class="date-input w-full border border-gray-300 rounded px-2 py-1 focus:ring-cyan-500 focus:border-cyan-500"
                >
                @error('seo_metrics_date')
                <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                @enderror
            </div>
        </div>

        <!-- Boolean Fields -->
        <div class="grid grid-cols-3 gap-4">
            <div>
                <label class="flex items-center">
                    <input type="checkbox" name="betting" value="1"
                           {{ old('betting') ? 'checked' : '' }}
                           class="mr-2 focus:ring-cyan-500">
                    Betting
                </label>
                @error('betting')
                <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                @enderror
            </div>
            <div>
                <label class="flex items-center">
                    <input type="checkbox" name="trading" value="1"
                           {{ old('trading') ? 'checked' : '' }}
                           class="mr-2 focus:ring-cyan-500">
                    Trading
                </label>
                @error('trading')
                <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                @enderror
            </div>
            <div>
                <label class="flex items-center">
                    <input type="checkbox" name="permanent_link" value="1"
                           {{ old('permanent_link') ? 'checked' : '' }}
                           class="mr-2 focus:ring-cyan-500">
                    LINK LIFETIME
                </label>
                @error('permanent_link')
                <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                @enderror
            </div>
            <div>
                <label class="flex items-center">
                    <input type="checkbox" name="more_than_one_link" value="1"
                           {{ old('more_than_one_link') ? 'checked' : '' }}
                           class="mr-2 focus:ring-cyan-500">
                    More than one link
                </label>
                @error('more_than_one_link')
                <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                @enderror
            </div>
        </div>

        <div class="grid grid-cols-3 gap-4">
            <div>
                <label class="flex items-center">
                    <input type="checkbox" name="copywriting" value="1"
                           {{ old('copywriting') ? 'checked' : '' }}
                           class="mr-2 focus:ring-cyan-500">
                    Copywriting
                </label>
                @error('copywriting')
                <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                @enderror
            </div>
            <div>
                <label class="flex items-center">
                    <input type="checkbox" name="no_sponsored_tag" value="1"
                           {{ old('no_sponsored_tag') ? 'checked' : '' }}
                           class="mr-2 focus:ring-cyan-500">
                    No Sponsored Tag
                </label>
                @error('no_sponsored_tag')
                <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                @enderror
            </div>
            <div>
                <label class="flex items-center">
                    <input type="checkbox" name="social_media_sharing" value="1"
                           {{ old('social_media_sharing') ? 'checked' : '' }}
                           class="mr-2 focus:ring-cyan-500">
                    Social Media Sharing
                </label>
                @error('social_media_sharing')
                <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                @enderror
            </div>
        </div>

        <div class="grid grid-cols-3 gap-4">
            <div>
                <label class="flex items-center">
                    <input type="checkbox" name="post_in_homepage" value="1"
                           {{ old('post_in_homepage') ? 'checked' : '' }}
                           class="mr-2 focus:ring-cyan-500">
                    Post in Homepage
                </label>
                @error('post_in_homepage')
                <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                @enderror
            </div>
        </div>

        <!-- Additional Details -->
        <div class="grid grid-cols-2 gap-4">
            <div>
                <label class="block text-gray-700 font-medium mb-1">Notes</label>
                <textarea
                    name="notes"
                    rows="3"
                    class="w-full border border-gray-300 rounded px-2 py-1 focus:ring-cyan-500 focus:border-cyan-500"
                >{{ old('notes') }}</textarea>
                @error('notes')
                <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                @enderror
            </div>
            <div>
                <label class="block text-gray-700 font-medium mb-1">Internal Notes</label>
                <textarea
                    name="extra_notes"
                    rows="3"
                    class="w-full border border-gray-300 rounded px-2 py-1 focus:ring-cyan-500 focus:border-cyan-500"
                >{{ old('extra_notes') }}</textarea>
                @error('extra_notes')
                <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                @enderror
            </div>
        </div>

        <!-- Categories (Multi-select) -->
        <div>
            <label class="block text-gray-700 font-medium mb-1">Categories</label>
            <select
                id="categorySelect"
                name="category_ids[]"
                multiple
                class="w-full border border-gray-300 rounded px-2 py-1 focus:ring-cyan-500 focus:border-cyan-500"
            >

            @foreach($categories as $cat)
                    <option
                        value="{{ $cat->id }}"
                        {{ (collect(old('category_ids'))->contains($cat->id)) ? 'selected' : '' }}
                    >
                        {{ $cat->name }}
                    </option>
                @endforeach
            </select>
        </div>

        <!-- Submit -->
        <button
            type="submit"
            class="bg-cyan-600 text-white px-16 py-2 rounded shadow hover:bg-cyan-700
                       focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-cyan-500
                       text-lg"
        >
            Save
        </button>
    </form>
        </div>
    </div>
@endsection
@push('scripts')
    <script>
        $(document).ready(function() {
            $('#categorySelect').select2({
                placeholder: 'Select categories',
                closeOnSelect: false,
                width: '25%',
                dropdownAutoWidth: true
            });

            flatpickr('.date-input', {
                dateFormat: 'd/m/Y',   // what the user sees *and* what is sent to PHP
                allowInput: true
            });
        });


    </script>
@endpush
