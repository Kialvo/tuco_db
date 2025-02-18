@extends('layouts.dashboard')

@section('content')
    <h1 class="text-2xl font-bold mb-4">Create Website</h1>

    <form method="POST" action="{{ route('websites.store') }}" class="space-y-4">
        @csrf

        <!-- General Information -->
        <div class="grid grid-cols-2 gap-4">
            <div>
                <label class="block">Domain Name</label>
                <input type="text" name="domain_name" value="{{ old('domain_name') }}" class="w-full border-gray-300 rounded" required>
                @error('domain_name')
                <p class="text-red-500 text-sm">{{ $message }}</p>
                @enderror
            </div>
            <div>
                <label class="block">Status</label>
                <select name="status" class="w-full border-gray-300 rounded">
                    <option value="">-- None --</option>

                    <option value="active">
                        Active
                    </option>
                    <option value="past">
                        Past
                    </option>

                </select>
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
                    <option value="">-- None --</option>

                    <option value="VERTICAL">
                        Vertical
                    </option>
                    <option value="GENERALIST">
                        Generalist
                    </option>
                    <option value="LOCAL">
                        Local
                    </option>
                </select>
                @error('type_of_website')
                <p class="text-red-500 text-sm">{{ $message }}</p>
                @enderror
            </div>
            <div>
                <label class="block">Linkbuilder</label>
                <input type="text" name="linkbuilder" value="{{ old('linkbuilder') }}" class="w-full border-gray-300 rounded">
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
                        <option value="{{ $c->id }}" {{ old('country_id') == $c->id ? 'selected' : '' }}>
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
                        <option value="{{ $lang->id }}" {{ old('language_id') == $lang->id ? 'selected' : '' }}>
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
                        <option value="{{ $cnt->id }}" {{ old('contact_id') == $cnt->id ? 'selected' : '' }}>
                            {{ $cnt->name }}
                        </option>
                    @endforeach
                </select>
                @error('contact_id')
                <p class="text-red-500 text-sm">{{ $message }}</p>
                @enderror
            </div>
            <!-- Optional: Include currency if available. Make sure to pass $currencies if needed. -->
            <div>
                <label class="block">Currency</label>
                <select name="currency_code" class="w-full border-gray-300 rounded">
                    <option value="">-- None --</option>

                            <option value="EUR">
                                EUR
                            </option>
                            <option value="USD">
                                USD
                            </option>

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
                <input type="number" step="0.01" name="publisher_price" value="{{ old('publisher_price') }}" class="w-full border-gray-300 rounded">
                @error('publisher_price')
                <p class="text-red-500 text-sm">{{ $message }}</p>
                @enderror
            </div>
            <div>
                <label class="block">Date Publisher Price</label>
                <input type="date" name="date_publisher_price" value="{{ old('date_publisher_price') }}" class="w-full border-gray-300 rounded">
                @error('date_publisher_price')
                <p class="text-red-500 text-sm">{{ $message }}</p>
                @enderror
            </div>
            <div>
                <label class="block">Link Insertion Price</label>
                <input type="number" step="0.01" name="link_insertion_price" value="{{ old('link_insertion_price') }}" class="w-full border-gray-300 rounded">
                @error('link_insertion_price')
                <p class="text-red-500 text-sm">{{ $message }}</p>
                @enderror
            </div>
        </div>

        <div class="grid grid-cols-3 gap-4">
            <div>
                <label class="block">No Follow Price</label>
                <input type="number" step="0.01" name="no_follow_price" value="{{ old('no_follow_price') }}" class="w-full border-gray-300 rounded">
                @error('no_follow_price')
                <p class="text-red-500 text-sm">{{ $message }}</p>
                @enderror
            </div>
            <div>
                <label class="block">Special Topic Price</label>
                <input type="number" step="0.01" name="special_topic_price" value="{{ old('special_topic_price') }}" class="w-full border-gray-300 rounded">
                @error('special_topic_price')
                <p class="text-red-500 text-sm">{{ $message }}</p>
                @enderror
            </div>
            <div>
                <label class="block">Profit</label>
                <input type="number" step="0.01" name="profit" value="{{ old('profit') }}" class="w-full border-gray-300 rounded">
                @error('profit')
                <p class="text-red-500 text-sm">{{ $message }}</p>
                @enderror
            </div>
        </div>

        <!-- Evaluations -->
        <div class="grid grid-cols-2 gap-4">
            <div>
                <label class="block">Automatic Evaluation</label>
                <input type="number" step="0.01" name="automatic_evaluation" value="{{ old('automatic_evaluation') }}" class="w-full border-gray-300 rounded">
                @error('automatic_evaluation')
                <p class="text-red-500 text-sm">{{ $message }}</p>
                @enderror
            </div>
            <div>
                <label class="block">Kialvo Evaluation</label>
                <input type="number" step="0.01" name="kialvo_evaluation" value="{{ old('kialvo_evaluation') }}" class="w-full border-gray-300 rounded">
                @error('kialvo_evaluation')
                <p class="text-red-500 text-sm">{{ $message }}</p>
                @enderror
            </div>
        </div>

        <div>
            <label class="block">Date Kialvo Evaluation</label>
            <input type="date" name="date_kialvo_evaluation" value="{{ old('date_kialvo_evaluation') }}" class="w-full border-gray-300 rounded">
            @error('date_kialvo_evaluation')
            <p class="text-red-500 text-sm">{{ $message }}</p>
            @enderror
        </div>

        <!-- SEO Metrics -->
        <div class="grid grid-cols-4 gap-4">
            <div>
                <label class="block">DA</label>
                <input type="number" name="DA" value="{{ old('DA') }}" class="w-full border-gray-300 rounded">
                @error('DA')
                <p class="text-red-500 text-sm">{{ $message }}</p>
                @enderror
            </div>
            <div>
                <label class="block">PA</label>
                <input type="number" name="PA" value="{{ old('PA') }}" class="w-full border-gray-300 rounded">
                @error('PA')
                <p class="text-red-500 text-sm">{{ $message }}</p>
                @enderror
            </div>
            <div>
                <label class="block">TC</label>
                <input type="number" name="TC" value="{{ old('TC') }}" class="w-full border-gray-300 rounded">
                @error('TC')
                <p class="text-red-500 text-sm">{{ $message }}</p>
                @enderror
            </div>
            <div>
                <label class="block">CF</label>
                <input type="number" name="CF" value="{{ old('CF') }}" class="w-full border-gray-300 rounded">
                @error('CF')
                <p class="text-red-500 text-sm">{{ $message }}</p>
                @enderror
            </div>
        </div>

        <div class="grid grid-cols-4 gap-4">
            <div>
                <label class="block">DR</label>
                <input type="number" name="DR" value="{{ old('DR') }}" class="w-full border-gray-300 rounded">
                @error('DR')
                <p class="text-red-500 text-sm">{{ $message }}</p>
                @enderror
            </div>
            <div>
                <label class="block">UR</label>
                <input type="number" name="UR" value="{{ old('UR') }}" class="w-full border-gray-300 rounded">
                @error('UR')
                <p class="text-red-500 text-sm">{{ $message }}</p>
                @enderror
            </div>
            <div>
                <label class="block">ZA</label>
                <input type="number" name="ZA" value="{{ old('ZA') }}" class="w-full border-gray-300 rounded">
                @error('ZA')
                <p class="text-red-500 text-sm">{{ $message }}</p>
                @enderror
            </div>
            <div>
                <label class="block">AS (Metric)</label>
                <input type="number" name="as_metric" value="{{ old('as_metric') }}" class="w-full border-gray-300 rounded">
                @error('as_metric')
                <p class="text-red-500 text-sm">{{ $message }}</p>
                @enderror
            </div>
        </div>

        <div class="grid grid-cols-4 gap-4">
            <div>
                <label class="block">SEO Zoom</label>
                <input type="text" name="seozoom" value="{{ old('seozoom') }}" class="w-full border-gray-300 rounded">
                @error('seozoom')
                <p class="text-red-500 text-sm">{{ $message }}</p>
                @enderror
            </div>
            <div>
                <label class="block">TF vs CF</label>
                <input type="number" step="0.01" name="TF_vs_CF" value="{{ old('TF_vs_CF') }}" class="w-full border-gray-300 rounded">
                @error('TF_vs_CF')
                <p class="text-red-500 text-sm">{{ $message }}</p>
                @enderror
            </div>
            <div>
                <label class="block">Semrush Traffic</label>
                <input type="number" name="semrush_traffic" value="{{ old('semrush_traffic') }}" class="w-full border-gray-300 rounded">
                @error('semrush_traffic')
                <p class="text-red-500 text-sm">{{ $message }}</p>
                @enderror
            </div>
            <div>
                <label class="block">Ahrefs Keyword</label>
                <input type="number" name="ahrefs_keyword" value="{{ old('ahrefs_keyword') }}" class="w-full border-gray-300 rounded">
                @error('ahrefs_keyword')
                <p class="text-red-500 text-sm">{{ $message }}</p>
                @enderror
            </div>
        </div>

        <div class="grid grid-cols-4 gap-4">
            <div>
                <label class="block">Ahrefs Traffic</label>
                <input type="number" name="ahrefs_traffic" value="{{ old('ahrefs_traffic') }}" class="w-full border-gray-300 rounded">
                @error('ahrefs_traffic')
                <p class="text-red-500 text-sm">{{ $message }}</p>
                @enderror
            </div>
            <div>
                <label class="block">Keyword vs Traffic</label>
                <input type="number" step="0.01" name="keyword_vs_traffic" value="{{ old('keyword_vs_traffic') }}" class="w-full border-gray-300 rounded">
                @error('keyword_vs_traffic')
                <p class="text-red-500 text-sm">{{ $message }}</p>
                @enderror
            </div>
            <div>
                <label class="block">SEO Metrics Date</label>
                <input type="date" name="seo_metrics_date" value="{{ old('seo_metrics_date') }}" class="w-full border-gray-300 rounded">
                @error('seo_metrics_date')
                <p class="text-red-500 text-sm">{{ $message }}</p>
                @enderror
            </div>
        </div>

        <!-- Boolean Fields -->
        <div class="grid grid-cols-3 gap-4">
            <div>
                <label class="flex items-center">
                    <input type="checkbox" name="betting" value="1" {{ old('betting') ? 'checked' : '' }} class="mr-2">
                    Betting
                </label>
                @error('betting')
                <p class="text-red-500 text-sm">{{ $message }}</p>
                @enderror
            </div>
            <div>
                <label class="flex items-center">
                    <input type="checkbox" name="trading" value="1" {{ old('trading') ? 'checked' : '' }} class="mr-2">
                    Trading
                </label>
                @error('trading')
                <p class="text-red-500 text-sm">{{ $message }}</p>
                @enderror
            </div>
            <div>
                <label class="flex items-center">
                    <input type="checkbox" name="more_than_one_link" value="1" {{ old('more_than_one_link') ? 'checked' : '' }} class="mr-2">
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
                    <input type="checkbox" name="copywriting" value="1" {{ old('copywriting') ? 'checked' : '' }} class="mr-2">
                    Copywriting
                </label>
                @error('copywriting')
                <p class="text-red-500 text-sm">{{ $message }}</p>
                @enderror
            </div>
            <div>
                <label class="flex items-center">
                    <input type="checkbox" name="no_sponsored_tag" value="1" {{ old('no_sponsored_tag') ? 'checked' : '' }} class="mr-2">
                    No Sponsored Tag
                </label>
                @error('no_sponsored_tag')
                <p class="text-red-500 text-sm">{{ $message }}</p>
                @enderror
            </div>
            <div>
                <label class="flex items-center">
                    <input type="checkbox" name="social_media_sharing" value="1" {{ old('social_media_sharing') ? 'checked' : '' }} class="mr-2">
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
                    <input type="checkbox" name="post_in_homepage" value="1" {{ old('post_in_homepage') ? 'checked' : '' }} class="mr-2">
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
                <label class="block">Extra Notes</label>
                <textarea name="extra_notes" class="w-full border-gray-300 rounded" rows="3">{{ old('extra_notes') }}</textarea>
                @error('extra_notes')
                <p class="text-red-500 text-sm">{{ $message }}</p>
                @enderror
            </div>
        </div>

        <!-- Categories (Multi-select) -->
        <div>
            <label class="block">Categories</label>
            <select name="category_ids[]" multiple class="w-full border-gray-300 rounded">
                @foreach($categories as $cat)
                    <option value="{{ $cat->id }}" {{ (collect(old('category_ids'))->contains($cat->id)) ? 'selected' : '' }}>
                        {{ $cat->name }}
                    </option>
                @endforeach
            </select>
        </div>

        <button type="submit" class="bg-blue-600 text-white px-4 py-2 rounded">Save</button>
    </form>
@endsection
