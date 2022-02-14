<x-app-layout :title="$title">
    <x-success-alert />
    <x-create-link label="{{ __('Nové místo uložení') }}" link="{{ route('locations.create') }}" />
    <a href="{{ route('locations.export') }}" class="inline-block mt-3 text-sm font-semibold">
        {{ __('Exportovat') }}
    </a>
    <livewire:locations-table :types="$labels" />
</x-app-layout>
