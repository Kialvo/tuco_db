<!DOCTYPE html>
<html>
<head>
    <title>Websites PDF Export</title>
    <style>
        table { width: 100%; border-collapse: collapse; margin-top: 10px; table-layout: fixed; }
        th, td { border: 1px solid #ccc; padding: 6px; font-size: 12px; word-wrap: break-word; }
        th { background: #eee; }
        body { font-family: sans-serif; }
        h2 { margin: 0; padding: 0; }
        .page-break { page-break-after: always; }
    </style>
</head>
<body>
<h2>Favorites for {{ $user->name }}</h2>
@php
    $chunks = collect($websites ?? [])->chunk(250);
@endphp
@foreach($chunks as $chunk)
<table>
    <thead>
    <tr>
        <th>ID</th>
        <th>Domain</th>
        <th>Publisher Price</th>
        <th>Kialvo</th>
        <th>Profit</th>
        <th>DA</th>
        <th>Country</th>
        <th>Language</th>
        <th>Contact</th>
        <th>Categories</th>
        <th>Status</th>
        <th>Currency</th>
        <th>Date Publisher Price</th>
        <th>Link Insertion Price</th>
        <th>No Follow Price</th>
        <th>Special Topic Price</th>
        <th>Linkbuilder</th>
        <th>Automatic Eval</th>
        <th>Date Kialvo Eval</th>
        <th>Type of Website</th>
        <th>PA</th>
        <th>TF</th>
        <th>CF</th>
        <th>DR</th>
        <th>UR</th>
        <th>ZA</th>
        <th>AS</th>
        <th>SEO Zoom</th>
        <th>TF vs CF</th>
        <th>Semrush Traffic</th>
        <th>Ahrefs Keyword</th>
        <th>Ahrefs Traffic</th>
        <th>Keyword vs Traffic</th>
        <th>SEO Metrics Date</th>
        <th>Betting</th>
        <th>Trading</th>
        <th>LINK LIFETIME</th>
        <th>More than 1 link</th>
        <th>Copywriting</th>
        <th>No Sponsored Tag</th>
        <th>Social Media Sharing</th>
        <th>Post in Homepage</th>
        <th>Date Added</th>
        <th>Notes</th>
        <th>Internal Notes</th>
    </tr>
    </thead>
    <tbody>
    @foreach($chunk as $web)
        <tr>
            <td>{{ $web->id }}</td>
            <td>{{ $web->domain_name }}</td>
            <td>{{ $web->publisher_price }}</td>
            <td>{{ $web->kialvo_evaluation }}</td>
            <td>{{ $web->profit }}</td>
            <td>{{ $web->DA }}</td>
            <td>{{ optional($web->country)->country_name }}</td>
            <td>{{ optional($web->language)->name }}</td>
            <td>{{ optional($web->contact)->name }}</td>
            <td>{{ $web->categories->pluck('name')->join(', ') }}</td>
            <td>{{ $web->status }}</td>
            <td>{{ $web->currency_code }}</td>
            <td>{{ $web->date_publisher_price }}</td>
            <td>{{ $web->link_insertion_price }}</td>
            <td>{{ $web->no_follow_price }}</td>
            <td>{{ $web->special_topic_price }}</td>
            <td>{{ $web->linkbuilder }}</td>
            <td>{{ $web->automatic_evaluation }}</td>
            <td>{{ $web->date_kialvo_evaluation }}</td>
            <td>{{ $web->type_of_website }}</td>
            <td>{{ $web->PA }}</td>
            <td>{{ $web->TF }}</td>
            <td>{{ $web->CF }}</td>
            <td>{{ $web->DR }}</td>
            <td>{{ $web->UR }}</td>
            <td>{{ $web->ZA }}</td>
            <td>{{ $web->as_metric }}</td>
            <td>{{ $web->seozoom }}</td>
            <td>{{ $web->TF_vs_CF }}</td>
            <td>{{ $web->semrush_traffic }}</td>
            <td>{{ $web->ahrefs_keyword }}</td>
            <td>{{ $web->ahrefs_traffic }}</td>
            <td>{{ $web->keyword_vs_traffic }}</td>
            <td>{{ $web->seo_metrics_date }}</td>
            <td>{{ $web->betting ? 'Yes' : 'No' }}</td>
            <td>{{ $web->trading ? 'Yes' : 'No' }}</td>
            <td>{{ $web->permanent_link ? 'Yes' : 'No' }}</td>
            <td>{{ $web->more_than_one_link ? 'Yes' : 'No' }}</td>
            <td>{{ $web->copywriting ? 'Yes' : 'No' }}</td>
            <td>{{ $web->no_sponsored_tag ? 'Yes' : 'No' }}</td>
            <td>{{ $web->social_media_sharing ? 'Yes' : 'No' }}</td>
            <td>{{ $web->post_in_homepage ? 'Yes' : 'No' }}</td>
            <td>{{ $web->created_at }}</td>
            <td>{{ $web->notes }}</td>
            <td>{{ $web->extra_notes }}</td>
        </tr>
    @endforeach
    </tbody>
</table>
@if(! $loop->last)
    <div class="page-break"></div>
@endif
@endforeach
</body>
</html>
