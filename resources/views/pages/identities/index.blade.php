<x-app-layout :title="$title">
    <x-success-alert />
    @can('manage-metadata')
        <div class="flex items-center justify-between gap-4 flex-wrap mb-6">
            <x-create-link label="{{ __('hiko.new_identity') }}" link="{{ route('identities.create') }}" />
            <a href="{{ route('identities.export') }}" class="inline-block mt-3 text-sm font-semibold">
                {{ __('hiko.export') }}
            </a>
        </div>
    @endcan
    <livewire:identities-table :labels="$labels" />
</x-app-layout>
