<x-app-layout :title="$title">
    <x-success-alert />
    <x-create-link label="{{ __('hiko.new_location') }}" link="{{ route('locations.create') }}" />
    <a href="{{ route('locations.export') }}" class="inline-block mt-3 text-sm font-semibold">
        {{ __('hiko.export') }}
    </a>
    <livewire:locations-table :types="$labels" />
</x-app-layout>
