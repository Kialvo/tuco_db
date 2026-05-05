@php
    $isGuestUser = auth()->check() && auth()->user()->isGuest();
@endphp

@extends('layouts.dashboard')

@section('content')
    <div class="max-w-4xl mx-auto bg-white p-6 rounded shadow">
        <h1 class="text-2xl font-bold mb-4">Website Details</h1>

        <div class="mb-2"><strong>Domain Name:</strong> {{ $website->domain_name }}</div>
        <div class="mb-2"><strong>Kialvo Evaluation:</strong> {{ $website->kialvo_evaluation }}</div>
        <div class="mb-2"><strong>Type of Website:</strong> {{ $website->type_of_website }}</div>
        <div class="mb-2"><strong>DA:</strong> {{ $website->DA }}</div>
        <div class="mb-2"><strong>PA:</strong> {{ $website->PA }}</div>
        <div class="mb-2"><strong>TF:</strong> {{ $website->TF }}</div>
        <div class="mb-2"><strong>CF:</strong> {{ $website->CF }}</div>
        <div class="mb-2"><strong>DR:</strong> {{ $website->DR }}</div>
        <div class="mb-2"><strong>UR:</strong> {{ $website->UR }}</div>
        <div class="mb-2"><strong>ZA:</strong> {{ $website->ZA }}</div>
        <div class="mb-2"><strong>AS Metric:</strong> {{ $website->as_metric }}</div>
        <div class="mb-2"><strong>SEO Zoom:</strong> {{ $website->seozoom }}</div>
        <div class="mb-2"><strong>TF vs CF:</strong> {{ $website->TF_vs_CF }}</div>
        <div class="mb-2"><strong>Semrush Traffic:</strong> {{ $website->semrush_traffic }}</div>
        <div class="mb-2"><strong>Ahrefs Keyword:</strong> {{ $website->ahrefs_keyword }}</div>
        <div class="mb-2"><strong>Ahrefs Traffic:</strong> {{ $website->ahrefs_traffic }}</div>
        <div class="mb-2"><strong>Keyword vs Traffic:</strong> {{ $website->keyword_vs_traffic }}</div>
        <div class="mb-2"><strong>Betting:</strong> {{ $website->betting ? 'Yes' : 'No' }}</div>
        <div class="mb-2"><strong>Trading:</strong> {{ $website->trading ? 'Yes' : 'No' }}</div>
        <div class="mb-2"><strong>LINK LIFETIME:</strong> {{ $website->permanent_link ? 'Yes' : 'No' }}</div>
        <div class="mb-2"><strong>More than 1 Link:</strong> {{ $website->more_than_one_link ? 'Yes' : 'No' }}</div>
        <div class="mb-2"><strong>Sponsored Tag:</strong> {{ $website->no_sponsored_tag ? 'No' : 'Yes' }}</div>
        <div class="mb-2"><strong>Social Media Sharing:</strong> {{ $website->social_media_sharing ? 'Yes' : 'No' }}</div>
        <div class="mb-2"><strong>Post in Homepage:</strong> {{ $website->post_in_homepage ? 'Yes' : 'No' }}</div>
        <div class="mb-2"><strong>Notes:</strong> {{ $website->notes }}</div>
        <div class="mb-2"><strong>Country:</strong> {{ optional($website->country)->country_name ?? 'N/A' }}</div>
        <div class="mb-2"><strong>Language:</strong> {{ optional($website->language)->name ?? 'N/A' }}</div>
        <div class="mb-2">
            <strong>Categories:</strong>
            @if($website->categories->isNotEmpty())
                {{ $website->categories->pluck('name')->join(', ') }}
            @else
                N/A
            @endif
        </div>

        @unless($isGuestUser)
            <hr class="my-4">
            <div class="mb-2"><strong>Internal Notes:</strong> {{ $website->extra_notes }}</div>
            <div class="mb-2"><strong>Status:</strong> {{ $website->status }}</div>
            <div class="mb-2"><strong>Publisher Price:</strong> {{ $website->publisher_price }}</div>
            <div class="mb-2"><strong>Original Publisher Price:</strong> {{ $website->original_publisher_price }}</div>
            <div class="mb-2"><strong>Date Publisher Price:</strong> {{ $website->date_publisher_price }}</div>
            <div class="mb-2"><strong>Link Insertion Price:</strong> {{ $website->link_insertion_price }}</div>
            <div class="mb-2"><strong>Original Link Insertion Price:</strong> {{ $website->original_link_insertion_price }}</div>
            <div class="mb-2"><strong>No Follow Price:</strong> {{ $website->no_follow_price }}</div>
            <div class="mb-2"><strong>Original No Follow Price:</strong> {{ $website->original_no_follow_price }}</div>
            <div class="mb-2"><strong>Special Topic Price:</strong> {{ $website->special_topic_price }}</div>
            <div class="mb-2"><strong>Original Special Topic Price:</strong> {{ $website->original_special_topic_price }}</div>
            <div class="mb-2"><strong>Profit:</strong> {{ $website->profit }}</div>
            <div class="mb-2"><strong>Linkbuilder:</strong> {{ $website->linkbuilder }}</div>
            <div class="mb-2"><strong>Automatic Evaluation:</strong> {{ $website->automatic_evaluation }}</div>
            <div class="mb-2"><strong>Date Kialvo Evaluation:</strong> {{ $website->date_kialvo_evaluation }}</div>
            <div class="mb-2"><strong>SEO Metrics Date:</strong> {{ $website->seo_metrics_date }}</div>
            <div class="mb-2"><strong>Copywriting:</strong> {{ $website->copywriting ? 'Yes' : 'No' }}</div>
            <div class="mb-2"><strong>Date Added:</strong> {{ $website->date_added }}</div>
            <div class="mb-2"><strong>Contact:</strong> {{ optional($website->contact)->name ?? 'N/A' }}</div>
        @endunless

        <div class="mt-6">
            @unless($isGuestUser)
                <a href="{{ route('websites.edit', $website->id) }}" class="bg-blue-600 text-white px-4 py-2 rounded">Edit</a>
            @endunless
            <a href="{{ route('websites.index') }}" class="ml-4 text-blue-600 underline">Back to list</a>
        </div>
    </div>
@endsection
