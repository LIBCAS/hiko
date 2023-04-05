<x-app-layout :title="$title">
    <x-success-alert />
    @can('manage-metadata')
        <x-create-link label="{{ __('hiko.new_identity') }}" link="{{ route('identities.create') }}" />
        <a href="{{ route('identities.export') }}" class="inline-block mt-3 text-sm font-semibold">
            {{ __('hiko.export') }}
        </a>
    @endcan
    <livewire:identities-table :labels="$labels" />
</x-app-layout>
