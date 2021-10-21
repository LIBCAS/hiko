<x-app-layout :title="$title">
    <x-success-alert />
    @can('manage-metadata')
        <x-create-link label="{{ __('Nový dopis') }}" link="{{ route('letters.create') }}" />
    @endcan
    <livewire:letters-table />
    @push('styles')
        <style>
            .table input,
            .table select,
            .table .table-cell {
                max-width: 140px;
            }
        </style>
    @endpush
</x-app-layout>
