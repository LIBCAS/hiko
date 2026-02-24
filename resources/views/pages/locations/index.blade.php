<x-app-layout :title="$title">
    <x-success-alert />
    @can('manage-metadata')
        <div class="flex items-center justify-between gap-4 flex-wrap mb-6">
            <div class="flex items-center space-x-4">
                <x-create-link label="{{ __('hiko.new_location') }}" link="{{ route('locations.create') }}" />
                @can('manage-users')
                    <x-create-link label="{{ __('hiko.new_global_location') }}" link="{{ route('global.locations.create') }}" />
                @endcan
            </div>
            <div class="flex items-center gap-4">
                <a href="{{ route('locations.export') }}" class="inline-block text-sm font-semibold">
                    {{ __('hiko.export') }}
                </a>
                <a href="{{ route('locations.validation') }}" class="inline-block text-sm font-semibold">
                    {{ __('hiko.input_control') }}
                </a>
                <a href="{{ route('locations.local-merge') }}" class="inline-block text-sm font-semibold">
                    {{ __('hiko.local_merging') }}
                </a>
                @can('manage-users')
                    <a href="{{ route('locations.global-merge') }}" class="inline-block text-sm font-semibold">
                        {{ __('hiko.global_merging') }}
                    </a>
                @endcan
            </div>
        </div>
    @endcan
    <livewire:locations-table :types="$labels" />
</x-app-layout>
