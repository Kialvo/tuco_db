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
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.4/js/jquery.dataTables.min.js"></script>

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
