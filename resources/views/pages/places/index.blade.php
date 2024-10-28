<x-app-layout :title="$title">
    <x-success-alert />
    @can('manage-metadata')
        <x-create-link label="{{ __('hiko.new_place') }}" link="{{ route('places.create') }}" />
        <a href="{{ route('places.export') }}" class="inline-block mt-3 text-sm font-semibold">
            {{ __('hiko.export') }}
        </a>
    @endcan
    <livewire:places-table />
</x-app-layout>
