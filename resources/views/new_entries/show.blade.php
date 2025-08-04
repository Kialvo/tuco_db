@extends('layouts.dashboard')

@section('content')
    <div class="max-w-4xl mx-auto bg-white p-6 rounded shadow">
        <h1 class="text-2xl font-bold mb-4">New-Entry Details</h1>

        {{-- ───── BASIC FIELDS ───── --}}
        <div class="mb-2"><strong>Domain Name:</strong> {{ $new_entry->domain_name }}</div>
        <div class="mb-2"><strong>Status:</strong> {{ $new_entry->status }}</div>

        {{-- ───── PRICE COLUMNS ───── --}}
        <div class="mb-2"><strong>Publisher Price:</strong> {{ $new_entry->publisher_price }}</div>
        <div class="mb-2"><strong>Original Publisher Price:</strong> {{ $new_entry->original_publisher_price }}</div>
        <div class="mb-2"><strong>Date Publisher Price:</strong> {{ $new_entry->date_publisher_price }}</div>

        <div class="mb-2"><strong>Link Insertion Price:</strong> {{ $new_entry->link_insertion_price }}</div>
        <div class="mb-2"><strong>Original Link Insertion Price:</strong> {{ $new_entry->original_link_insertion_price }}</div>

        <div class="mb-2"><strong>No-follow Price:</strong> {{ $new_entry->no_follow_price }}</div>
        <div class="mb-2"><strong>Original No-follow Price:</strong> {{ $new_entry->original_no_follow_price }}</div>

        <div class="mb-2"><strong>Special-topic Price:</strong> {{ $new_entry->special_topic_price }}</div>
        <div class="mb-2"><strong>Original Special-topic Price:</strong> {{ $new_entry->original_special_topic_price }}</div>

        <div class="mb-2"><strong>Banner Price:</strong> {{ $new_entry->banner_price }}</div>
        <div class="mb-2"><strong>Site-wide Link Price:</strong> {{ $new_entry->sitewide_link_price }}</div>

        {{-- ───── CALCULATED / MISC ───── --}}
        <div class="mb-2"><strong>Profit:</strong> {{ $new_entry->profit }}</div>
        <div class="mb-2"><strong>Linkbuilder:</strong> {{ $new_entry->linkbuilder }}</div>
        <div class="mb-2"><strong>Automatic Evaluation:</strong> {{ $new_entry->automatic_evaluation }}</div>
        <div class="mb-2"><strong>Kialvo Evaluation:</strong> {{ $new_entry->kialvo_evaluation }}</div>
        <div class="mb-2"><strong>Date Kialvo Evaluation:</strong> {{ $new_entry->date_kialvo_evaluation }}</div>
        <div class="mb-2"><strong>Type of Website:</strong> {{ $new_entry->type_of_website }}</div>

        {{-- ───── SEO METRICS ───── --}}
        <div class="grid grid-cols-4 gap-2 text-sm my-3">
            <div><strong>DA:</strong> {{ $new_entry->DA }}</div>
            <div><strong>PA:</strong> {{ $new_entry->PA }}</div>
            <div><strong>TF:</strong> {{ $new_entry->TF }}</div>
            <div><strong>CF:</strong> {{ $new_entry->CF }}</div>
            <div><strong>DR:</strong> {{ $new_entry->DR }}</div>
            <div><strong>UR:</strong> {{ $new_entry->UR }}</div>
            <div><strong>ZA:</strong> {{ $new_entry->ZA }}</div>
            <div><strong>AS Metric:</strong> {{ $new_entry->as_metric }}</div>
        </div>

        <div class="mb-2"><strong>SEOzoom:</strong> {{ $new_entry->seozoom }}</div>
        <div class="mb-2"><strong>TF vs CF:</strong> {{ $new_entry->TF_vs_CF }}</div>
        <div class="mb-2"><strong>Semrush Traffic:</strong> {{ $new_entry->semrush_traffic }}</div>
        <div class="mb-2"><strong>Ahrefs Keyword:</strong> {{ $new_entry->ahrefs_keyword }}</div>
        <div class="mb-2"><strong>Ahrefs Traffic:</strong> {{ $new_entry->ahrefs_traffic }}</div>
        <div class="mb-2"><strong>Keyword vs Traffic:</strong> {{ $new_entry->keyword_vs_traffic }}</div>
        <div class="mb-2"><strong>SEO Metrics Date:</strong> {{ $new_entry->seo_metrics_date }}</div>

        {{-- ───── FLAGS ───── --}}
        @php
            $bool = fn($v) => $v ? 'Yes' : 'No';
        @endphp
        <div class="grid grid-cols-3 gap-2 text-sm my-3">
            <div><strong>Betting:</strong> {{ $bool($new_entry->betting) }}</div>
            <div><strong>Trading:</strong> {{ $bool($new_entry->trading) }}</div>
            <div><strong>Permanent Link:</strong> {{ $bool($new_entry->permanent_link) }}</div>
            <div><strong>More than 1 Link:</strong> {{ $bool($new_entry->more_than_one_link) }}</div>
            <div><strong>Copywriting:</strong> {{ $bool($new_entry->copywriting) }}</div>
            <div><strong>No Sponsored Tag:</strong> {{ $bool($new_entry->no_sponsored_tag) }}</div>
            <div><strong>Social Media Sharing:</strong> {{ $bool($new_entry->social_media_sharing) }}</div>
            <div><strong>Post in Homepage:</strong> {{ $bool($new_entry->post_in_homepage) }}</div>
            <div><strong>Copied to Overview:</strong> {{ $bool($new_entry->copied_to_overview) }}</div>
        </div>

        {{-- ───── DATES & NOTES ───── --}}
        <div class="mb-2"><strong>First Contact Date:</strong> {{ $new_entry->first_contact_date }}</div>
        <div class="mb-2"><strong>Date Added:</strong> {{ $new_entry->date_added }}</div>
        <div class="mb-2"><strong>Extra Notes:</strong> {{ $new_entry->extra_notes }}</div>

        {{-- ───── FOREIGN KEYS ───── --}}
        <div class="mb-2"><strong>Country:</strong>
            {{ optional($new_entry->country)->country_name ?? 'N/A' }}</div>
        <div class="mb-2"><strong>Language:</strong>
            {{ optional($new_entry->language)->name ?? 'N/A' }}</div>
        <div class="mb-2"><strong>Contact:</strong>
            {{ optional($new_entry->contact)->name ?? 'N/A' }}</div>
        <div class="mb-2"><strong>Categories:</strong>
            {{ $new_entry->categories->isNotEmpty()
                ? $new_entry->categories->pluck('name')->join(', ')
                : 'N/A' }}
        </div>

        {{-- ───── ACTIONS ───── --}}
        <div class="mt-6">
            <a href="{{ route('new_entries.edit', $new_entry->id) }}"
               class="bg-blue-600 text-white px-4 py-2 rounded shadow hover:bg-blue-700">
                Edit
            </a>
            <a href="{{ route('new_entries.index') }}"
               class="ml-4 text-blue-600 underline">
                Back to list
            </a>
        </div>
    </div>
@endsection
