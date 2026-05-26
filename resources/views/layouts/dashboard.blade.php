<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ config('app.name', 'Linkinablink') }}@hasSection('title') — @yield('title')@endif</title>

    <link rel="icon" type="image/x-icon" href="{{ asset('favicon.png') }}">

    {{-- Vite (Tailwind + app.js) --}}
    @vite(['resources/css/app.css', 'resources/js/app.js'])

    {{-- DataTables, Select2, flatpickr stay (needed by admin index pages).
         FontAwesome dropped: all icons now render via the inline-SVG <x-icon> component. --}}
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.4/css/jquery.dataTables.min.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/buttons/2.3.6/css/buttons.dataTables.min.css">
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet"/>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.4/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/buttons/2.3.6/js/dataTables.buttons.min.js"></script>
    <script src="https://cdn.datatables.net/buttons/2.3.6/js/buttons.html5.min.js"></script>
    <script src="https://cdn.datatables.net/buttons/2.3.6/js/buttons.print.min.js"></script>
    <script src="https://cdn.datatables.net/buttons/2.3.6/js/buttons.flash.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.2.7/pdfmake.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.2.7/vfs_fonts.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
    
    {{-- SweetAlert dropped: window.Swal is provided by resources/js/swal-shim.js (loaded via Vite) --}}

    @stack('styles')
    @stack('scripts')
</head>
<body class="bg-gray-50 text-gray-800 antialiased h-screen overflow-hidden flex">

    @include('layouts.partials.sidebar')

    @hasSection('filters')
        {{-- Three-column: sidebar (already rendered) + sticky filter aside + main --}}
        <aside class="w-[268px] bg-white border-r border-gray-200 flex flex-col flex-shrink-0 overflow-hidden">
            @yield('filters')
        </aside>
        <div class="flex-1 flex flex-col overflow-hidden">
            @hasSection('pageHeader')
                @yield('pageHeader')
            @endif
            <main class="flex-1 overflow-auto">
                @yield('content')
            </main>
        </div>
    @else
        {{-- Two-column: sidebar + main (existing behaviour) --}}
        <main class="flex-1 overflow-auto">
            @yield('content')
        </main>
    @endif

    {{-- Cart drawer for guest users --}}
    @auth
        @if(auth()->user()->isGuest())
            @include('marketplace.partials.cart-drawer')
        @endif
    @endauth

    {{-- Flash --}}
    @if(session('status'))
        <div x-data="{ show: true }" x-init="setTimeout(() => show = false, 4000)" x-show="show" x-transition.duration.300ms
             class="fixed bottom-4 right-4 max-w-sm bg-white border border-green-200 shadow-lg rounded-lg px-4 py-3 z-50 flex items-start gap-2 text-sm text-gray-700">
            <x-icon name="check-circle" class="text-green-600 mt-0.5 flex-shrink-0" />
            <span>{{ session('status') }}</span>
        </div>
    @endif

    <script>
        $.ajaxSetup({
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
            }
        });

        
    </script>
<script
  src="https://ai-orchestration-platform-codex-wid.vercel.app/embed/loader.js"
  data-site-key="site_link_in_a_blink_staging_website_01bafb2d"
  data-api-base-url="https://ai-orchestration-platform-codex-staging.up.railway.app"
  async
></script>
</body>
</html>
