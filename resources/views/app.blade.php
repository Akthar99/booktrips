<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" @class(['dark' => ($appearance ?? 'system') == 'dark'])>
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">

        {{-- Browsers cache favicons hard: the filemtime stamp forces the new logo after each deploy. --}}
        <link rel="icon" href="/favicon.ico?v={{ filemtime(public_path('favicon.ico')) }}" sizes="any">
        <link rel="icon" href="/favicon.svg?v={{ filemtime(public_path('favicon.svg')) }}" type="image/svg+xml">
        <link rel="apple-touch-icon" href="/apple-touch-icon.png?v={{ filemtime(public_path('apple-touch-icon.png')) }}">

        @fonts

        @viteReactRefresh
        @vite(['resources/css/app.css', 'resources/js/app.tsx', "resources/js/pages/{$page['component']}.tsx"])
        <x-inertia::head>
            <title>{{ config('app.name', 'Laravel') }}</title>
        </x-inertia::head>
    </head>
    <body class="font-sans antialiased">
        <x-inertia::app />
    </body>
</html>
