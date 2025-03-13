<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Dashboard Layout</title>

    {{-- Vite loading for Tailwind/JS (original references) --}}
    @vite(['resources/css/app.css', 'resources/js/app.js'])

    {{-- DataTables & other dependencies (same as in your original code) --}}
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.4/css/jquery.dataTables.min.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/buttons/2.3.6/css/buttons.dataTables.min.css" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">


    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.4/js/jquery.dataTables.min.js"></script>
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <script src="https://cdn.datatables.net/buttons/2.3.6/js/dataTables.buttons.min.js"></script>
    <script src="https://cdn.datatables.net/buttons/2.3.6/js/buttons.html5.min.js"></script>
    <script src="https://cdn.datatables.net/buttons/2.3.6/js/buttons.print.min.js"></script>
    <script src="https://cdn.datatables.net/buttons/2.3.6/js/buttons.flash.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.2.7/pdfmake.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.2.7/vfs_fonts.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    {{-- Select2 (unchanged from your code) --}}
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>

    @stack('scripts')
</head>
<body class="antialiased bg-gray-100">
<div class="flex min-h-screen">
    {{-- ========== SIDEBAR ========== --}}
    <aside class="w-64 flex-shrink-0 bg-slate-900 text-white flex flex-col">

    {{-- Logo/Brand area --}}
        <div class="h-16 flex items-center justify-center border-b border-slate-700">
            <img
                src="{{ asset('images/logo.png') }}"
                alt="MotherLink Logo"
                class="h-14 w-auto"
            />

        </div>


        {{-- Main Navigation --}}
        <nav class="flex-1 px-4 space-y-2 mt-4">
            <a href="{{ route('dashboard') }}"
               class="block px-3 py-2 rounded hover:bg-slate-800 transition
                      {{ request()->routeIs('dashboard') ? 'bg-slate-800' : '' }}">
                <i class="fas fa-tachometer-alt w-5 inline-block me-2"></i>
                Dashboard
            </a>

            <a href="{{ route('contacts.index') }}"
               class="block px-3 py-2 rounded hover:bg-slate-800 transition">
                <i class="fas fa-address-book w-5 inline-block me-2"></i>
                Contacts
            </a>

            <a href="{{ route('websites.index') }}"
               class="block px-3 py-2 rounded hover:bg-slate-800 transition">
                <i class="fas fa-globe w-5 inline-block me-2"></i>
                Websites
            </a>

            @if(Auth::check() && Auth::user()->role === 'admin')
                <a href="{{ route('admin.users.index') }}"
                   class="block px-3 py-2 rounded hover:bg-slate-800 transition">
                    <i class="fas fa-users-cog w-5 inline-block me-2"></i>
                    Manage Users
                </a>
            @endif
        </nav>

        {{-- Logout Form --}}
        <form method="POST" action="{{ route('logout') }}" class="p-4 border-t border-slate-700">
            @csrf
            <button type="submit" class="w-full text-left bg-slate-800 hover:bg-slate-700 py-2 px-3 rounded flex items-center">
                <i class="fas fa-sign-out-alt w-5 inline-block me-2"></i>
                Logout
            </button>
        </form>
    </aside>
    {{-- ========== END SIDEBAR ========== --}}

    {{-- ========== MAIN CONTENT ========== --}}
    <main class="flex-1 p-6">
        {{-- Content will go here --}}
        @yield('content')
    </main>
    {{-- ========== END MAIN CONTENT ========== --}}
</div>

{{-- If you need to init DataTables, do so below or remove this --}}
<script>
    $(document).ready(function() {
        // $('#example-table').DataTable();
    });
</script>

</body>
</html>
