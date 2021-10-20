<x-app-layout :title="$title">
    <x-success-alert />
    @can('manage-metadata')
        <x-create-link label="{{ __('Nová profese') }}" link="{{ route('professions.create') }}" />
        <a href="{{ route('professions.export') }}" class="inline-block mt-3 text-sm font-semibold">
            {{ __('Exportovat') }}
        </a>
    @endcan
    <livewire:professions-table />
    @can('manage-metadata')
        <x-create-link label="{{ __('Nová kategorie profese') }}" link="{{ route('professions.category.create') }}"
            class="mt-16" />
        <a href="{{ route('professions.category.export') }}" class="inline-block mt-3 text-sm font-semibold">
            {{ __('Exportovat') }}
        </a>
    @endcan
    @cannot('manage-metadata')
        <p class="mt-16 font-bold">
            {{ __('Kategorie profese') }}
        </p>
    @endcannot
    <livewire:profession-category-table />
</x-app-layout>
