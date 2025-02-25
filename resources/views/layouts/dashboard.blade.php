<!-- resources/views/layouts/dashboard.blade.php -->
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Dashboard Layout</title>
    @vite(['resources/css/app.css', 'resources/js/app.js']) <!-- If using Vite -->

    <!-- In your layouts/dashboard.blade.php, before closing </head> or at the end of the body -->
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.4/css/jquery.dataTables.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <!-- DataTables Buttons extension CSS -->
    <link rel="stylesheet" href="https://cdn.datatables.net/buttons/2.3.6/css/buttons.dataTables.min.css" />

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.4/js/jquery.dataTables.min.js"></script>
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <!-- DataTables Buttons extension JS -->
    <script src="https://cdn.datatables.net/buttons/2.3.6/js/dataTables.buttons.min.js"></script>

    <!-- HTML5 button dependencies -->
    <script src="https://cdn.datatables.net/buttons/2.3.6/js/buttons.html5.min.js"></script>
    <script src="https://cdn.datatables.net/buttons/2.3.6/js/buttons.print.min.js"></script>
    <script src="https://cdn.datatables.net/buttons/2.3.6/js/buttons.flash.min.js"></script>

    <!-- pdfmake scripts required for pdfHtml5 -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.2.7/pdfmake.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.2.7/vfs_fonts.js"></script>

    <!-- In the head section -->
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />

    <!-- Before closing body tag -->
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
    @stack('scripts')

</head>
<body class="antialiased bg-gray-100">
<div class="flex min-h-screen">
    <!-- SIDEBAR -->
    <aside class="w-64 bg-blue-900 text-white p-4">
        <h2 class="text-xl font-bold mb-6">Main Menu</h2>

        <nav class="flex flex-col space-y-2">
            <!-- Common link: Dashboard -->
            <a href="{{ route('dashboard') }}" class="hover:bg-blue-700 px-2 py-1 rounded">
                Dashboard
            </a>

            <!-- Websites link: accessible by both roles -->
            <a href="{{ route('contacts.index') }}" class="hover:bg-blue-700 px-2 py-1 rounded">
                Contacts
            </a>
            <!-- Websites link: accessible by both roles -->
            <a href="{{ route('websites.index') }}" class="hover:bg-blue-700 px-2 py-1 rounded">
                Websites
            </a>

            <!-- Admin-only link: Manage Users -->
            @if(Auth::check() && Auth::user()->role === 'admin')
                <a href="{{ route('admin.users.index') }}" class="hover:bg-blue-700 px-2 py-1 rounded">
                    Manage Users
                </a>
            @endif
            <!-- Logout form -->
            <form method="POST" action="{{ route('logout') }}" class="mt-4">
                @csrf
                <button type="submit" class="w-full text-left hover:bg-blue-700 px-2 py-1 rounded">
                    Logout
                </button>
            </form>
        </nav>
    </aside>

    <!-- MAIN CONTENT -->
    <main class="flex-1 p-6">
        @yield('content')
    </main>
</div>
</body>
</html>
