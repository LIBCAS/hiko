<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="min-h-screen">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ __('hiko.correspondence') }} {{ config('app.name') }}</title>
    <link rel="stylesheet" href="{{ asset(mix('/app.css', 'dist')) }}">
    <script src="{{ asset(mix('/app.js', 'dist')) }}" defer></script>
    <link rel="icon" type="image/svg+xml"
        href="data:image/svg+xml,<svg xmlns=%22http://www.w3.org/2000/svg%22 width=%22256%22 height=%22256%22 viewBox=%220 0 100 100%22><text x=%2250%%22 y=%2250%%22 dominant-baseline=%22central%22 text-anchor=%22middle%22 font-size=%2290%22>✉️</text></svg>" />
    @livewireStyles
    @stack('styles')
</head>

<body class="flex flex-col min-h-screen font-sans antialiased bg-gray-100">
    <header class="w-full px-6 bg-white shadow">
        @include('layouts.navigation')
    </header>
    <main class="px-6 py-12">
        <div class="container mx-auto">
            <h1 class="mb-6 text-xl font-semibold">
                {{ $title }}
            </h1>
            {{ $slot }}
        </div>
    </main>
    <footer class="px-6 py-6 mt-auto">
        <div class="container mx-auto mt-6">
            <p class="font-semibold">
                <a href="https://github.com/LIBCAS/hiko/wiki" target="_blank"
                    class="inline-flex items-center hover:underline">
                    <x-icons.question-mark-circle class="w-5 h-5 mr-1" />
                    <span>{{ __('hiko.help') }}</span>
                </a>
            </p>
            <p class="mt-2 text-sm text-gray-500">
                {{ date('Y') }} HIKO – {{ __('hiko.correspondence') }} {{ config('app.name') }}, v.{{ config('hiko.version') }}
            </p>
        </div>
    </footer>
    @livewireScripts
    @stack('scripts')
</body>

</html>
