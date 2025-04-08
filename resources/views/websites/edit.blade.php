@extends('layouts.dashboard')

@section('content')
    <h1 class="text-2xl font-bold mb-4">Edit Website</h1>

    <form method="POST" action="{{ route('websites.update', $website->id) }}" class="space-y-4">
        @csrf
        @method('PUT')

        <!-- General Information -->
        <div class="grid grid-cols-2 gap-4">
            <div>
                <label class="block">Domain Name</label>
                <input type="text" name="domain_name" value="{{ old('domain_name', $website->domain_name) }}" class="w-full border-gray-300 rounded" required>
                @error('domain_name')
                <p class="text-red-500 text-sm">{{ $message }}</p>
                @enderror
            </div>
            <div>
                <label class="block">Status</label>
                <input type="text" name="status" value="{{ old('status', $website->status) }}" class="w-full border-gray-300 rounded">
                @error('status')
                <p class="text-red-500 text-sm">{{ $message }}</p>
                @enderror
            </div>
        </div>

        <!-- Additional General Fields -->
        <div class="grid grid-cols-2 gap-4">
            <div>
                <label class="block">Type of Website</label>
                <select name="type_of_website" class="w-full border-gray-300 rounded">
                    <option value="">-- Select Type --</option>
                    @foreach(['VERTICAL', 'GENERALIST', 'LOCAL'] as $type)
                        <option value="{{ $type }}" {{ old('type_of_website', $website->type_of_website) == $type ? 'selected' : '' }}>
                            {{ $type }}
                        </option>
                    @endforeach
                </select>
                @error('type_of_website')
                <p class="text-red-500 text-sm">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label class="block">Linkbuilder</label>
                <input type="text" name="linkbuilder" value="{{ old('linkbuilder', $website->linkbuilder) }}" class="w-full border-gray-300 rounded">
                @error('linkbuilder')
                <p class="text-red-500 text-sm">{{ $message }}</p>
                @enderror
            </div>
        </div>

        <!-- Foreign Keys -->
        <div class="grid grid-cols-2 gap-4">
            <div>
                <label class="block">Country</label>
                <select name="country_id" class="w-full border-gray-300 rounded">
                    <option value="">-- None --</option>
                    @foreach($countries as $c)
                        <option value="{{ $c->id }}" {{ old('country_id', $website->country_id) == $c->id ? 'selected' : '' }}>
                            {{ $c->country_name }}
                        </option>
                    @endforeach
                </select>
                @error('country_id')
                <p class="text-red-500 text-sm">{{ $message }}</p>
                @enderror
            </div>
            <div>
                <label class="block">Language</label>
                <select name="language_id" class="w-full border-gray-300 rounded">
                    <option value="">-- None --</option>
                    @foreach($languages as $lang)
                        <option value="{{ $lang->id }}" {{ old('language_id', $website->language_id) == $lang->id ? 'selected' : '' }}>
                            {{ $lang->name }}
                        </option>
                    @endforeach
                </select>
                @error('language_id')
                <p class="text-red-500 text-sm">{{ $message }}</p>
                @enderror
            </div>
        </div>

        <div class="grid grid-cols-2 gap-4">
            <div>
                <label class="block">Contact</label>
                <select name="contact_id" class="w-full border-gray-300 rounded">
                    <option value="">-- None --</option>
                    @foreach($contacts as $cnt)
                        <option value="{{ $cnt->id }}" {{ old('contact_id', $website->contact_id) == $cnt->id ? 'selected' : '' }}>
                            {{ $cnt->name }}
                        </option>
                    @endforeach
                </select>
                @error('contact_id')
                <p class="text-red-500 text-sm">{{ $message }}</p>
                @enderror
            </div>
            <!-- Optional: Currency -->
            <div>
                <label class="block">Currency</label>
                <select name="currency_code" class="w-full border-gray-300 rounded">
                    <option value="">-- None --</option>
                    <option value="EUR" {{ old('currency_code', $website->currency_code) == 'EUR' ? 'selected' : '' }}>EUR</option>
                    <option value="USD" {{ old('currency_code', $website->currency_code) == 'USD' ? 'selected' : '' }}>USD</option>
                </select>
                @error('currency_code')
                <p class="text-red-500 text-sm">{{ $message }}</p>
                @enderror
            </div>

        </div>

        <!-- Price Details & Dates -->
        <div class="grid grid-cols-3 gap-4">
            <div>
                <label class="block">Publisher Price</label>
                <input type="number" step="0.01" name="publisher_price" value="{{ old('publisher_price', $website->publisher_price) }}" class="w-full border-gray-300 rounded">
                @error('publisher_price')
                <p class="text-red-500 text-sm">{{ $message }}</p>
                @enderror
            </div>
            <div>
                <label class="block">Original Publisher Price</label>
                <input type="number" step="0.01" name="original_publisher_price" value="{{ old('original_publisher_price', $website->original_publisher_price) }}" class="w-full border-gray-300 rounded">
                @error('original_publisher_price')
                <p class="text-red-500 text-sm">{{ $message }}</p>
                @enderror
            </div>
            <div>
                <label class="block">Date Publisher Price</label>
                <input type="date"
                       name="date_publisher_price"
                       value="{{ old('date_publisher_price', $website->date_publisher_price ? \Carbon\Carbon::parse($website->date_publisher_price)->format('Y-d-m') : '') }}"
                       class="w-full border-gray-300 rounded">
                @error('date_publisher_price')
                <p class="text-red-500 text-sm">{{ $message }}</p>
                @enderror
            </div>
            <div>
                <label class="block">Link Insertion Price</label>
                <input type="number" step="0.01" name="link_insertion_price" value="{{ old('link_insertion_price', $website->link_insertion_price) }}" class="w-full border-gray-300 rounded">
                @error('link_insertion_price')
                <p class="text-red-500 text-sm">{{ $message }}</p>
                @enderror
            </div>
            <div>
                <label class="block">Original Link Insertion Price</label>
                <input type="number" step="0.01" name="original_link_insertion_price" value="{{ old('original_link_insertion_price', $website->original_link_insertion_price) }}" class="w-full border-gray-300 rounded">
                @error('original_link_insertion_price')
                <p class="text-red-500 text-sm">{{ $message }}</p>
                @enderror
            </div>
        </div>

        <div class="grid grid-cols-3 gap-4">
            <div>
                <label class="block">No Follow Price</label>
                <input type="number" step="0.01" name="no_follow_price" value="{{ old('no_follow_price', $website->no_follow_price) }}" class="w-full border-gray-300 rounded">
                @error('no_follow_price')
                <p class="text-red-500 text-sm">{{ $message }}</p>
                @enderror
            </div>
            <div>
                <label class="block">Original No Follow Price</label>
                <input type="number" step="0.01" name="original_no_follow_price" value="{{ old('original_no_follow_price', $website->original_no_follow_price) }}" class="w-full border-gray-300 rounded">
                @error('original_no_follow_price')
                <p class="text-red-500 text-sm">{{ $message }}</p>
                @enderror
            </div>
            <div>
                <label class="block">Special Topic Price</label>
                <input type="number" step="0.01" name="special_topic_price" value="{{ old('special_topic_price', $website->special_topic_price) }}" class="w-full border-gray-300 rounded">
                @error('special_topic_price')
                <p class="text-red-500 text-sm">{{ $message }}</p>
                @enderror
            </div>
            <div>
                <label class="block">Original Special Topic Price</label>
                <input type="number" step="0.01" name="original_special_topic_price" value="{{ old('original_special_topic_price', $website->original_special_topic_price) }}" class="w-full border-gray-300 rounded">
                @error('original_special_topic_price')
                <p class="text-red-500 text-sm">{{ $message }}</p>
                @enderror
            </div>
            <div>
                <label class="block">Profit</label>
                <input type="number" step="0.01" name="profit" value="{{ old('profit', $website->profit) }}" class="w-full border-gray-300 rounded">
                @error('profit')
                <p class="text-red-500 text-sm">{{ $message }}</p>
                @enderror
            </div>
        </div>

        <!-- Evaluations -->
        <div class="grid grid-cols-2 gap-4">
            <div>
                <label class="block">Automatic Evaluation</label>
                <input type="number"  name="automatic_evaluation" value="{{ old('automatic_evaluation', $website->automatic_evaluation) }}" class="w-full border-gray-300 rounded">
                @error('automatic_evaluation')
                <p class="text-red-500 text-sm">{{ $message }}</p>
                @enderror
            </div>
            <div>
                <label class="block">Kialvo Evaluation</label>
                <input type="number" step="0.01" name="kialvo_evaluation" value="{{ old('kialvo_evaluation', $website->kialvo_evaluation) }}" class="w-full border-gray-300 rounded">
                @error('kialvo_evaluation')
                <p class="text-red-500 text-sm">{{ $message }}</p>
                @enderror
            </div>
        </div>

        <div>
            <label class="block">Date Kialvo Evaluation</label>
            <input type="date"
                   name="date_kialvo_evaluation"
                   value="{{ old('date_kialvo_evaluation', $website->date_kialvo_evaluation ? \Carbon\Carbon::parse($website->date_kialvo_evaluation)->format('Y-d-m') : '') }}"
                   class="w-full border-gray-300 rounded">
            @error('date_kialvo_evaluation')
            <p class="text-red-500 text-sm">{{ $message }}</p>
            @enderror
        </div>

        <!-- SEO Metrics -->
        <div class="grid grid-cols-4 gap-4">
            <div>
                <label class="block">DA</label>
                <input type="number" name="DA" value="{{ old('DA', $website->DA) }}" class="w-full border-gray-300 rounded">
                @error('DA')
                <p class="text-red-500 text-sm">{{ $message }}</p>
                @enderror
            </div>
            <div>
                <label class="block">PA</label>
                <input type="number" name="PA" value="{{ old('PA', $website->PA) }}" class="w-full border-gray-300 rounded">
                @error('PA')
                <p class="text-red-500 text-sm">{{ $message }}</p>
                @enderror
            </div>
            <div>
                <label class="block">TF</label>
                <input type="number" name="TF" value="{{ old('TF', $website->TF) }}" class="w-full border-gray-300 rounded">
                @error('TF')
                <p class="text-red-500 text-sm">{{ $message }}</p>
                @enderror
            </div>
            <div>
                <label class="block">CF</label>
                <input type="number" name="CF" value="{{ old('CF', $website->CF) }}" class="w-full border-gray-300 rounded">
                @error('CF')
                <p class="text-red-500 text-sm">{{ $message }}</p>
                @enderror
            </div>
        </div>

        <div class="grid grid-cols-4 gap-4">
            <div>
                <label class="block">DR</label>
                <input type="number" name="DR" value="{{ old('DR', $website->DR) }}" class="w-full border-gray-300 rounded">
                @error('DR')
                <p class="text-red-500 text-sm">{{ $message }}</p>
                @enderror
            </div>
            <div>
                <label class="block">UR</label>
                <input type="number" name="UR" value="{{ old('UR', $website->UR) }}" class="w-full border-gray-300 rounded">
                @error('UR')
                <p class="text-red-500 text-sm">{{ $message }}</p>
                @enderror
            </div>
            <div>
                <label class="block">ZA</label>
                <input type="number" name="ZA" value="{{ old('ZA', $website->ZA) }}" class="w-full border-gray-300 rounded">
                @error('ZA')
                <p class="text-red-500 text-sm">{{ $message }}</p>
                @enderror
            </div>
            <div>
                <label class="block">AS (Metric)</label>
                <input type="number" name="as_metric" value="{{ old('as_metric', $website->as_metric) }}" class="w-full border-gray-300 rounded">
                @error('as_metric')
                <p class="text-red-500 text-sm">{{ $message }}</p>
                @enderror
            </div>
        </div>

        <div class="grid grid-cols-4 gap-4">
            <div>
                <label class="block">SEO Zoom</label>
                <input type="text" name="seozoom" value="{{ old('seozoom', $website->seozoom) }}" class="w-full border-gray-300 rounded">
                @error('seozoom')
                <p class="text-red-500 text-sm">{{ $message }}</p>
                @enderror
            </div>
            <div>
                <label class="block">TF vs CF</label>
                <input type="number" step="0.01" name="TF_vs_CF" value="{{ old('TF_vs_CF', $website->TF_vs_CF) }}" class="w-full border-gray-300 rounded">
                @error('TF_vs_CF')
                <p class="text-red-500 text-sm">{{ $message }}</p>
                @enderror
            </div>
            <div>
                <label class="block">Semrush Traffic</label>
                <input type="number" name="semrush_traffic" value="{{ old('semrush_traffic', $website->semrush_traffic) }}" class="w-full border-gray-300 rounded">
                @error('semrush_traffic')
                <p class="text-red-500 text-sm">{{ $message }}</p>
                @enderror
            </div>
            <div>
                <label class="block">Ahrefs Keyword</label>
                <input type="number" name="ahrefs_keyword" value="{{ old('ahrefs_keyword', $website->ahrefs_keyword) }}" class="w-full border-gray-300 rounded">
                @error('ahrefs_keyword')
                <p class="text-red-500 text-sm">{{ $message }}</p>
                @enderror
            </div>
        </div>

        <div class="grid grid-cols-4 gap-4">
            <div>
                <label class="block">Ahrefs Traffic</label>
                <input type="number" name="ahrefs_traffic" value="{{ old('ahrefs_traffic', $website->ahrefs_traffic) }}" class="w-full border-gray-300 rounded">
                @error('ahrefs_traffic')
                <p class="text-red-500 text-sm">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label class="block">SEO Metrics Date</label>
                <input type="date"
                       name="seo_metrics_date"
                       value="{{ old('seo_metrics_date', $website->seo_metrics_date ? \Carbon\Carbon::parse($website->seo_metrics_date)->format('Y-m-d') : '') }}"
                       class="w-full border-gray-300 rounded">
                @error('seo_metrics_date')
                <p class="text-red-500 text-sm">{{ $message }}</p>
                @enderror
            </div>
        </div>

        <!-- Boolean Fields -->
        <div class="grid grid-cols-3 gap-4">
            <div>
                <label class="flex items-center">
                    <input type="checkbox" name="betting" value="1" {{ old('betting', $website->betting) ? 'checked' : '' }} class="mr-2">
                    Betting
                </label>
                @error('betting')
                <p class="text-red-500 text-sm">{{ $message }}</p>
                @enderror
            </div>
            <div>
                <label class="flex items-center">
                    <input type="checkbox" name="trading" value="1" {{ old('trading', $website->trading) ? 'checked' : '' }} class="mr-2">
                    Trading
                </label>
                @error('trading')
                <p class="text-red-500 text-sm">{{ $message }}</p>
                @enderror
            </div>
            <div>
                <label class="flex items-center">
                    <input type="checkbox" name="permanent_link" value="1" {{ old('permanent_link', $website->permanent_link) ? 'checked' : '' }} class="mr-2">
                    Permanent Link
                </label>
                @error('permanent_link')
                <p class="text-red-500 text-sm">{{ $message }}</p>
                @enderror
            </div>
            <div>
                <label class="flex items-center">
                    <input type="checkbox" name="more_than_one_link" value="1" {{ old('more_than_one_link', $website->more_than_one_link) ? 'checked' : '' }} class="mr-2">
                    More than one link
                </label>
                @error('more_than_one_link')
                <p class="text-red-500 text-sm">{{ $message }}</p>
                @enderror
            </div>
        </div>

        <div class="grid grid-cols-3 gap-4">
            <div>
                <label class="flex items-center">
                    <input type="checkbox" name="copywriting" value="1" {{ old('copywriting', $website->copywriting) ? 'checked' : '' }} class="mr-2">
                    Copywriting
                </label>
                @error('copywriting')
                <p class="text-red-500 text-sm">{{ $message }}</p>
                @enderror
            </div>
            <div>
                <label class="flex items-center">
                    <input type="checkbox" name="no_sponsored_tag" value="1" {{ old('no_sponsored_tag', $website->no_sponsored_tag) ? 'checked' : '' }} class="mr-2">
                    No Sponsored Tag
                </label>
                @error('no_sponsored_tag')
                <p class="text-red-500 text-sm">{{ $message }}</p>
                @enderror
            </div>
            <div>
                <label class="flex items-center">
                    <input type="checkbox" name="social_media_sharing" value="1" {{ old('social_media_sharing', $website->social_media_sharing) ? 'checked' : '' }} class="mr-2">
                    Social Media Sharing
                </label>
                @error('social_media_sharing')
                <p class="text-red-500 text-sm">{{ $message }}</p>
                @enderror
            </div>
        </div>

        <div class="grid grid-cols-3 gap-4">
            <div>
                <label class="flex items-center">
                    <input type="checkbox" name="post_in_homepage" value="1" {{ old('post_in_homepage', $website->post_in_homepage) ? 'checked' : '' }} class="mr-2">
                    Post in Homepage
                </label>
                @error('post_in_homepage')
                <p class="text-red-500 text-sm">{{ $message }}</p>
                @enderror
            </div>
        </div>

        <!-- Additional Details -->
        <div class="grid grid-cols-2 gap-4">
            <div>
                <label class="block">Date Added</label>
                <input type="date"
                       name="date_added"
                       value="{{ old('date_added', $website->created_at ? \Carbon\Carbon::parse($website->created_at)->format('Y-d-m') : '') }}"
                       class="w-full border-gray-300 rounded">
                @error('date_added')
                <p class="text-red-500 text-sm">{{ $message }}</p>
                @enderror
            </div>
            <div>
                <label class="block">Extra Notes</label>
                <textarea name="extra_notes" class="w-full border-gray-300 rounded" rows="3">{{ old('extra_notes', $website->extra_notes) }}</textarea>
                @error('extra_notes')
                <p class="text-red-500 text-sm">{{ $message }}</p>
                @enderror
            </div>
        </div>

        <!-- Categories (Multi-select) -->
        <div>
            <label class="block">Categories</label>
            <select id="editCategorySelect" name="category_ids[]" multiple class="w-full border-gray-300 rounded">

            @foreach($categories as $cat)
                    <option value="{{ $cat->id }}" {{ (collect(old('category_ids', $website->categories->pluck('id')->toArray()))->contains($cat->id)) ? 'selected' : '' }}>
                        {{ $cat->name }}
                    </option>
                @endforeach
            </select>
        </div>

        <button type="submit" class="bg-cyan-600 text-white px-16 py-2 rounded shadow hover:bg-cyan-700
                       focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-cyan-500
                       text-lg">Update</button>

    </form>
@endsection
@push('scripts')
    <script>
        $(document).ready(function() {
            $('#editCategorySelect').select2({
                placeholder: 'Select categories',
                closeOnSelect: false,
                width: '25%',
                dropdownAutoWidth: true
            });
        });
    </script>
@endpush
