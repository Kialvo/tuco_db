<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ config('app.name', 'Linkinablink') }}@isset($title) — {{ $title }}@endisset</title>

    <link rel="icon" type="image/x-icon" href="{{ asset('favicon.png') }}">

    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-gray-50 text-gray-800 antialiased">

    <div class="min-h-screen flex items-center justify-center px-4 py-10">
        <div class="w-full max-w-md">

            {{-- Brand mark + tagline --}}
            <div class="text-center mb-8">
                <x-app-wordmark variant="dark" size="lg" class="justify-center" />
                @isset($heading)
                    <h1 class="text-2xl font-bold text-gray-800 mt-5">{{ $heading }}</h1>
                @endisset
                @isset($subheading)
                    <p class="text-gray-500 text-sm mt-1">{{ $subheading }}</p>
                @endisset
            </div>

            {{-- Card --}}
            <div class="bg-white rounded-2xl shadow-card border border-gray-200 p-7">
                {{ $slot }}
            </div>

            @isset($foot)
                <div class="mt-6 text-center">
                    {{ $foot }}
                </div>
            @endisset
        </div>
    </div>

    {{-- Toast for session('status') / errors --}}
    @if(session('status'))
        <div x-data="{ show: true }" x-init="setTimeout(() => show = false, 4000)" x-show="show" x-transition.duration.300ms
             class="fixed bottom-4 right-4 max-w-sm bg-white border border-green-200 shadow-lg rounded-lg px-4 py-3 z-50 flex items-start gap-2 text-sm text-gray-700">
            <x-icon name="check-circle" class="text-green-600 mt-0.5 flex-shrink-0" />
            <span>{{ session('status') }}</span>
        </div>
    @endif
</body>
</html>
