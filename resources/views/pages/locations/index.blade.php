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
                <x-loading-link href="{{ route('locations.export') }}">
                    {{ __('hiko.export') }}
                </x-loading-link>
                <x-loading-link href="{{ route('locations.validation') }}">
                    {{ __('hiko.input_control') }}
                </x-loading-link>
                <x-loading-link href="{{ route('locations.local-merge') }}">
                    {{ __('hiko.local_merging') }}
                </x-loading-link>
                @can('manage-users')
                    <x-loading-link href="{{ route('locations.global-merge') }}">
                        {{ __('hiko.global_merging') }}
                    </x-loading-link>
                @endcan
            </div>
        </div>
    @endcan
    <livewire:locations-table :types="$labels" />
</x-app-layout>
