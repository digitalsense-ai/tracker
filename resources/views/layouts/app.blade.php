<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>@yield('title', 'ORB')</title>
    
    @vite('resources/css/app.css') {{-- Loads Tailwind CSS via Vite --}}

    <!-- Bootstrap 5 CSS CDN -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet" />
</head>
<body class="bg-gray-100 text-gray-800 min-h-screen flex flex-col">

    <header class="bg-blue-600 text-white p-4 shadow">
        <div class="container mx-auto">
            <h1 class="text-xl font-semibold">ORB Dashboard</h1>
        </div>
    </header>

    <main class="flex-grow container mx-auto px-4 py-6">
        @yield('content')
    </main>

    <footer class="bg-white border-t text-center text-sm text-gray-500 py-4">
        &copy; {{ date('Y') }} ORB
    </footer>

    @vite('resources/js/app.js') {{-- Optional: if you use JS --}}

    <!-- Bootstrap 5 JS Bundle CDN (includes Popper) -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
