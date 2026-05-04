<!doctype html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport"
        content="width=device-width, user-scalable=no, initial-scale=1.0, maximum-scale=1.0, minimum-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="auth-session-started" content="{{ Auth::check() ? 'true' : 'false' }}">
    <meta name="auth-session-timeout-minutes" content="{{ config('session.lifetime') }}">
    <meta name="auth-logout-url" content="{{ route('logout', [], false) }}">
    <x-theme-preload />
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <title>{{ $title ?? config('app.name', 'Laravel') }}</title>
    @stack('css')
</head>
<body class="bg-gray-50 text-gray-900 transition-colors dark:bg-gray-950 dark:text-gray-100">
    <x-legal-links />
    {{ $slot }}
</body>
</html>
