<x-app-layout :title="$title">
    <x-success-alert />
    @can('manage-metadata')
        <x-create-link label="{{ __('Nová osoba / instituce') }}" link="{{ route('identities.create') }}" />
        <a href="{{ route('identities.export') }}" class="inline-block mt-3 text-sm font-semibold">
            {{ __('Exportovat') }}
        </a>
    @endcan
    <livewire:identities-table :labels="$labels" />
</x-app-layout>
