<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Agency Database') — Lead DB</title>

    <!-- Tailwind CSS via CDN (no build step required) -->
    <script src="https://cdn.tailwindcss.com"></script>

    <!-- Optional: configure Tailwind custom colors -->
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        primary: '#1E3A5F',
                        accent: '#2E75B6',
                    }
                }
            }
        }
    </script>
</head>

<body class="bg-gray-50 text-gray-800">

    <!-- Navigation Bar -->
    <nav class="bg-primary text-white shadow-md">
        <div class="max-w-7xl mx-auto px-4 py-3 flex justify-between items-center">
            <a href="/agencies" class="text-xl font-bold tracking-wide">
                🌐 Agency Lead DB
            </a>
            <div class="flex gap-4 text-sm items-center">
                <span class="text-blue-200 text-xs">{{ Auth::user()->name }}</span>
                <form method="POST" action="/logout">
                    @csrf
                    <button type="submit" class="hover:underline text-white opacity-75 hover:opacity-100">
                        Sign out
                    </button>
                </form>
            </div>
        </div>
    </nav>

    <!-- Page Content -->
    <main class="max-w-7xl mx-auto px-4 py-6">
        @yield('content')
    </main>

    <!-- Scripts slot -->
    @stack('scripts')

</body>

</html>
