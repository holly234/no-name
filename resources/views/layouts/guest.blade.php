<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ config('app.name', 'Laravel') }}</title>

        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />

        <!-- Scripts -->
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="font-sans text-[#111827] antialiased">
        <div class="flex min-h-screen items-center justify-center bg-[#F5F6F8] px-4 py-10">
            <div class="w-full max-w-md overflow-hidden rounded-2xl border border-[#E5E7EB] bg-white px-6 py-8 shadow-xl shadow-slate-900/5 sm:px-9 sm:py-10">
                {{ $slot }}
            </div>
        </div>
    </body>
</html>
