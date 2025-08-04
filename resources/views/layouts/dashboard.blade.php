<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Dashboard Layout</title>

    {{-- Vite  (Tailwind + app.js) --}}
    @vite(['resources/css/app.css', 'resources/js/app.js'])

    {{-- DataTables / Icons / Select2 --}}
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.4/css/jquery.dataTables.min.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/buttons/2.3.6/css/buttons.dataTables.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
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
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    {{-- Alpine.js for dropdown toggles --}}
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>

    <meta name="csrf-token" content="{{ csrf_token() }}">
    @stack('scripts')
</head>
<body class="antialiased bg-gray-100">
<div class="flex min-h-screen">
    {{-- ========== SIDEBAR ========== --}}
    <aside class="w-64 flex-shrink-0 bg-slate-900 text-white flex flex-col">

        {{-- Logo --}}
        <div class="h-16 flex items-center justify-center border-b border-slate-700">
            <img src="{{ asset('images/logo.png') }}" alt="MotherLink Logo" class="h-14 w-auto">
        </div>

        {{-- Navigation --}}
        <nav class="flex-1 px-4 mt-4 space-y-2 text-sm">

            {{-- Dashboard --}}
            <a href="{{ route('dashboard') }}"
               class="block px-3 py-2 rounded transition
                      hover:bg-slate-800 {{ request()->routeIs('dashboard') ? 'bg-slate-800' : '' }}">
                <i class="fas fa-tachometer-alt w-5 inline-block me-2"></i> Dashboard
            </a>

            {{-- ============ Websites ============ --}}
            <div x-data="{ open: {{ request()->routeIs('websites.*') || request()->routeIs('contacts.*') ? 'true' : 'false' }} }">
                <div class="flex items-center justify-between px-3 py-2 rounded transition
                            hover:bg-slate-800 {{ request()->routeIs('websites.*') ? 'bg-slate-800' : '' }}">
                    {{-- Main link --}}
                    <a href="{{ route('websites.index') }}" class="flex-1 inline-flex items-center">
                        <i class="fas fa-globe w-5 inline-block me-2"></i> Websites
                    </a>
                    {{-- Toggle --}}
                    <button @click="open = !open" class="focus:outline-none">
                        <i :class="open ? 'fas fa-chevron-up' : 'fas fa-chevron-down'" class="w-4"></i>
                    </button>
                </div>

                {{-- Sub‑menu --}}
                <div x-show="open" x-cloak class="space-y-1 mt-1 ps-6">
                    <a href="{{ route('contacts.index') }}"
                       class="block px-3 py-2 rounded transition hover:bg-slate-800
                              {{ request()->routeIs('contacts.*') ? 'bg-slate-800' : '' }}">
                        <i class="fas fa-address-book w-4 inline-block me-2"></i> Contacts
                    </a>
                </div>
            </div>
            {{-- ============ New Entry ============ --}}
            {{-- ============ New Entries ============ --}}
            <div
                x-data="{ open: {{ request()->routeIs('new_entries.*') || request()->routeIs('new_entries.historical*') ? 'true' : 'false' }} }"
            >
                <div class="flex items-center justify-between px-3 py-2 rounded transition
                hover:bg-slate-800 {{ request()->routeIs('new_entries.*') || request()->routeIs('new_entries.historical*') ? 'bg-slate-800' : '' }}">
                    {{-- Main link --}}
                    {{-- Main link --}}
                    <a href="{{ route('new_entries.index') }}" class="flex-1 inline-flex items-center">
                        {{-- icon that exists in FA 6.0-beta3 --}}
                        <i class="fas fa-folder-plus w-5 inline-block me-2"></i> New Entries
                    </a>


                    {{-- Toggle --}}
                    <button @click="open = !open" class="focus:outline-none">
                        <i :class="open ? 'fas fa-chevron-up' : 'fas fa-chevron-down'" class="w-4"></i>
                    </button>
                </div>

                {{-- Sub-menu --}}
                <div x-show="open" x-cloak class="space-y-1 mt-1 ps-6">
                    <a href="{{ route('historical_view.index') }}"
                       class="block px-3 py-2 rounded transition hover:bg-slate-800
              {{ request()->routeIs('historical_view.*') ? 'bg-slate-800' : '' }}">
                        <i class="fas fa-clock-rotate-left w-4 inline-block me-2"></i> Historical View
                    </a>
                </div>

            </div>



            {{-- ============ Storages ============ --}}
            <div x-data="{ open: {{ request()->routeIs('storages.*') || request()->routeIs('clients.*') || request()->routeIs('copy.*') ? 'true' : 'false' }} }">
                <div class="flex items-center justify-between px-3 py-2 rounded transition
                            hover:bg-slate-800 {{ request()->routeIs('storages.*') ? 'bg-slate-800' : '' }}">
                    {{-- Main link --}}
                    <a href="{{ route('storages.index') }}" class="flex-1 inline-flex items-center">
                        <i class="fas fa-warehouse w-5 inline-block me-2"></i> Storages
                    </a>
                    {{-- Toggle --}}
                    <button @click="open = !open" class="focus:outline-none">
                        <i :class="open ? 'fas fa-chevron-up' : 'fas fa-chevron-down'" class="w-4"></i>
                    </button>
                </div>

                {{-- Sub‑menu --}}
                <div x-show="open" x-cloak class="space-y-1 mt-1 ps-6">
                    <a href="{{ route('clients.index') }}"
                       class="block px-3 py-2 rounded transition hover:bg-slate-800
                              {{ request()->routeIs('clients.*') ? 'bg-slate-800' : '' }}">
                        <i class="fas fa-user-friends w-4 inline-block me-2"></i> Clients
                    </a>
                    <a href="{{ route('copy.index') }}"
                       class="block px-3 py-2 rounded transition hover:bg-slate-800
                              {{ request()->routeIs('copy.*') ? 'bg-slate-800' : '' }}">
                        <i class="fas fa-file-alt w-4 inline-block me-2"></i> Copy
                    </a>
                </div>
            </div>

            {{-- Admin --}}
            @if(Auth::check() && Auth::user()->role === 'admin')
                <a href="{{ route('admin.users.index') }}"
                   class="block px-3 py-2 rounded transition hover:bg-slate-800
                          {{ request()->routeIs('admin.users.*') ? 'bg-slate-800' : '' }}">
                    <i class="fas fa-users-cog w-5 inline-block me-2"></i> Manage Users
                </a>
            @endif

            {{-- resources/views/layouts/dashboard.blade.php --}}
            {{-- ============ Tools ============ --}}
            <div
                x-data="{ open: {{ request()->routeIs('tools.*') ? 'true' : 'false' }} }"
            >
                {{-- Parent row (label + chevron) --}}
                <div
                    class="flex items-center justify-between px-3 py-2 rounded transition
               hover:bg-slate-800 {{ request()->routeIs('tools.*') ? 'bg-slate-800' : '' }}"
                >
                    <a href="#"
                       class="flex-1 inline-flex items-center select-none"
                       @click.prevent="open = !open">
                        <i class="fas fa-tools w-5 inline-block me-2"></i> Tools
                    </a>

                    <button @click="open = !open" class="focus:outline-none">
                        <i :class="open ? 'fas fa-chevron-up' : 'fas fa-chevron-down'" class="w-4"></i>
                    </button>
                </div>

                {{-- Sub-menu --}}
                <div x-show="open" x-cloak class="space-y-1 mt-1 ps-6">
                    <a href="{{ route('tools.discover') }}"
                       class="block px-3 py-2 rounded transition hover:bg-slate-800
                  {{ request()->routeIs('tools.discover') ? 'bg-slate-800' : '' }}">
                        <i class="fas fa-search w-4 inline-block me-2"></i> Discover Websites
                    </a>

                    {{-- Add future tool links here --}}
                </div>
            </div>


        </nav>

        {{-- Logout --}}
        <form method="POST" action="{{ route('logout') }}" class="p-4 border-t border-slate-700">
            @csrf
            <button type="submit"
                    class="w-full flex items-center px-3 py-2 rounded bg-slate-800 hover:bg-slate-700">
                <i class="fas fa-sign-out-alt w-5 inline-block me-2"></i> Logout
            </button>
        </form>
    </aside>
    {{-- ========== END SIDEBAR ========== --}}

    {{-- ========== MAIN CONTENT ========== --}}
    <main class="flex-1 p-6">
        @yield('content')
    </main>
</div>

{{-- Optional: global DataTables init placeholder --}}
<script>
$.ajaxSetup({
    headers: {
        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
    }
});
</script>

</body>
</html>
