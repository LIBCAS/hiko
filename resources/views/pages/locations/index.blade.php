<x-app-layout :title="$title">
    <x-success-alert />
    <a href="{{ route('locations.create') }}" class="max-w-sm px-2 py-1 mb-6 font-bold text-primary hover:underline">
        + {{ __('Nové místo uložení') }}
    </a>
    <livewire:locations-table />
</x-app-layout>
