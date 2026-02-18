{{-- resources/views/storages/pdf.blade.php --}}
    <!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Storages Export</title>
    <style>
        /* Basic PDF-friendly styles */
        body {
            font-family: sans-serif;
            font-size: 10px;
            margin: 20px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
            table-layout: fixed;
        }
        th, td {
            border: 1px solid #444;
            padding: 4px;
            text-align: left;
            word-wrap: break-word;
        }
        th {
            background-color: #f0f0f0;
        }
        .page-break { page-break-after: always; }
    </style>
</head>
<body>
<h2>Storages Export</h2>

@php
    $chunks = collect($rows ?? [])->chunk(250);
@endphp
@foreach($chunks as $chunk)
<table>
    <thead>
    <tr>
        @foreach($header as $col)
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
