<x-app-layout :title="$title">
    <x-success-alert />
    @can('manage-metadata')
        <x-create-link label="{{ __('Nový dopis') }}" link="{{ route('letters.create') }}" />
    @endcan
    <livewire:letters-table />
</x-app-layout>
