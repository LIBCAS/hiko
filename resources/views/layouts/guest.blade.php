<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">
        <title>{{ __('hiko.correspondence') }} {{ config('app.name') }}</title>
        <link rel="stylesheet" href="{{ asset(mix('/app.css', 'dist')) }}">
        <script src="{{ asset(mix('/app.js', 'dist')) }}" defer></script>
        <link rel="icon" type="image/svg+xml" href="data:image/svg+xml,<svg xmlns=%22http://www.w3.org/2000/svg%22 width=%22256%22 height=%22256%22 viewBox=%220 0 100 100%22><text x=%2250%%22 y=%2250%%22 dominant-baseline=%22central%22 text-anchor=%22middle%22 font-size=%2290%22>✉️</text></svg>" />
    </head>
    <body class="px-6 font-sans antialiased text-black bg-white bg-mesh">
        {{ $slot }}
    </body>
</html>
