<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>{{ $title ?? 'Export' }}</title>
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
<h2>{{ $title ?? 'Export' }}</h2>
@php
    $chunks = collect($rows ?? [])->chunk(250);
@endphp
@foreach($chunks as $chunk)
<table>
    <thead>
    <tr>
        @foreach(($header ?? []) as $col)
            <th>{{ $col }}</th>
        @endforeach
    </tr>
    </thead>
    <tbody>
    @foreach($chunk as $row)
        <tr>
            @foreach($row as $cell)
                <td>{{ $cell }}</td>
            @endforeach
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
