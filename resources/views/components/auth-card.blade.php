@props(['title'])

<div class="flex flex-col items-center min-h-screen pt-6 sm:justify-center sm:pt-0">
    <h1 class="max-w-full w-72">
        <span class="block text-gray-500">{{ $title }}</span>
        <span class="text-lg font-semibold">{{ config('app.name') }}</span>
    </h1>
    <div class="max-w-full mt-6 w-72">
        {{ $slot }}
    </div>
</div>
