<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="min-h-screen">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ config('app.name') }}</title>
    <link rel="stylesheet" href="{{ asset('dist/app.css') }}">
    <script src="{{ asset('dist/app.js') }}" defer></script>
    @livewireStyles
</head>

<body class="flex flex-col min-h-screen font-sans antialiased bg-gray-100">
    <header class="w-full px-6 bg-white shadow">
        @include('layouts.navigation')
    </header>
    <main class="px-6 pt-12 ">
        <div class="container mx-auto ">
            <h1 class="mb-6 text-xl font-semibold">
                {{ $title }}
            </h1>
            {{ $slot }}
        </div>
    </main>
    <footer class="px-6 py-6 mt-auto">
        <div class="container mx-auto mt-6">
            <p class="font-semibold">
                <a href="https://github.com/JarkaP/hiko/wiki" target="_blank"
                    class="inline-flex items-center hover:underline">
                    <x-heroicon-o-question-mark-circle class="w-5 h-5 mr-1" />
                    <span>{{ __('Nápověda') }}</span>
                </a>
            </p>
            <p class="mt-2 text-sm text-gray-500">
                {{ date('Y') }} HIKO – {{ config('app.name') }}
            </p>
        </div>
    </footer>
    @livewireScripts
</body>

</html>
