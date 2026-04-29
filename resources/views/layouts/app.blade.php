<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">
        <meta name="auth-session-started" content="{{ Auth::check() ? 'true' : 'false' }}">
        <meta name="auth-session-timeout-minutes" content="{{ config('session.lifetime') }}">
        <meta name="auth-logout-url" content="{{ route('logout', [], false) }}">

        <title>{{ config('app.name', 'Laravel') }}</title>

        <!-- Fonts -->
        <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Nunito:wght@400;600;700&display=swap">

        <script>
            (() => {
                try {
                    const theme = localStorage.getItem('theme') || 'light';
                    document.documentElement.classList.toggle('dark', theme === 'dark');
                } catch (error) {}
            })();
        </script>

        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="font-sans antialiased text-gray-900 dark:text-gray-100">
        <div class="min-h-screen bg-gray-100 dark:bg-gray-950">
            @include('layouts.navigation')

            <!-- Page Heading -->
            <header class="bg-white shadow dark:bg-gray-900 dark:shadow-gray-950/40">
                <div class="max-w-7xl mx-auto py-6 px-4 sm:px-6 lg:px-8">
                    {{ $header }}
                </div>
            </header>

            <!-- Page Content -->
            <main>
                {{ $slot }}
            </main>
        </div>
    </body>
</html>
