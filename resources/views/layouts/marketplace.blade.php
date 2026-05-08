<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ config('app.name', 'Linkinablink') }}@isset($title) — {{ $title }}@endisset</title>

    @vite(['resources/css/app.css', 'resources/js/app.js'])

    @stack('head')
</head>

<body class="bg-gray-50 text-gray-800 antialiased h-screen overflow-hidden flex">

    @include('layouts.partials.sidebar')

    {{-- The page itself decides whether to render a filter panel + main, or just main. --}}
    <div class="flex-1 flex overflow-hidden">

        @isset($filters)
            {{ $filters }}
        @endisset

        <div class="flex-1 flex flex-col overflow-hidden">

            @isset($pageHeader)
                {{ $pageHeader }}
            @endisset

            <main class="flex-1 overflow-auto {{ $padding ?? 'p-6' }}">
                @yield('content')
                {{ $slot ?? '' }}
            </main>
        </div>

        @isset($drawer)
            {{ $drawer }}
        @endisset
    </div>

    {{-- Flash + global toast hook (Alpine-only, no SweetAlert) --}}
    @if(session('status'))
        <div x-data="{ show: true }" x-init="setTimeout(() => show = false, 3000)" x-show="show" x-transition.duration.300ms
             class="fixed bottom-4 right-4 bg-white border border-gray-200 shadow-lg rounded-lg px-4 py-3 z-50 flex items-center gap-2 text-sm text-gray-700">
            <x-icon name="check-circle" class="text-green-600" />
            {{ session('status') }}
        </div>
    @endif

    @stack('scripts')
</body>
</html>
