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
@php
    $yn = fn ($value) => $value === null ? '' : ($value ? 'YES' : 'NO');
    $sponsored = fn ($value) => $value === null ? '' : ($value ? 'NO' : 'YES');
    $chunks = collect($websites ?? [])->chunk(250);
@endphp
<h2>Domains for {{ $user->name }}</h2>
@foreach($chunks as $chunk)
<table>
    <thead>
    <tr>
        <th>Domain</th>
        <th>Notes</th>
        <th>Country</th>
        <th>Language</th>
        <th>Price</th>
        <th>Sensitive Topic Price</th>
        <th>Type of Website</th>
        <th>Categories</th>
        <th>DA</th>
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
        <th>Betting</th>
        <th>Trading</th>
        <th>LINK LIFETIME</th>
        <th>More than 1 link</th>
        <th>Sponsored Tag</th>
        <th>Social Media Sharing</th>
        <th>Post in Homepage</th>
    </tr>
    </thead>
    <tbody>
    @foreach($chunk as $web)
        <tr>
            <td>{{ $web->domain_name }}</td>
            <td>{{ $web->notes }}</td>
            <td>{{ optional($web->country)->country_name }}</td>
            <td>{{ optional($web->language)->name }}</td>
            <td>{{ $web->price }}</td>
            <td>{{ $web->sensitive_topic_price }}</td>
            <td>{{ $web->type_of_website }}</td>
            <td>{{ $web->categories->pluck('name')->join(', ') }}</td>
            <td>{{ $web->DA }}</td>
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
            <td>{{ $yn($web->betting) }}</td>
            <td>{{ $yn($web->trading) }}</td>
            <td>{{ $yn($web->permanent_link) }}</td>
            <td>{{ $yn($web->more_than_one_link) }}</td>
            <td>{{ $sponsored($web->no_sponsored_tag) }}</td>
            <td>{{ $yn($web->social_media_sharing) }}</td>
            <td>{{ $yn($web->post_in_homepage) }}</td>
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
