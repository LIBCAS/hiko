<x-app-layout :title="$title">
    <x-success-alert />
    <a href="{{ route('places.create') }}" class="max-w-sm px-2 py-1 mb-6 font-bold text-primary hover:underline">
        + {{ __('Nové místo') }}
    </a>
    <livewire:places-table />
</x-app-layout>
